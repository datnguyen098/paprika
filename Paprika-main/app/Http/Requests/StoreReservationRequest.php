<?php

namespace App\Http\Requests;

use App\Models\Branch;
use App\Models\RestaurantTable;
use App\Support\OpenDays;
use App\Support\OpeningHours;
use App\Support\ReservationTableAvailability;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('branch_id')) {
            $defaultBranchId = Branch::query()->active()->orderBy('sort_order')->value('id');

            if ($defaultBranchId) {
                $this->merge(['branch_id' => $defaultBranchId]);
            }
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('is_active', true)],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s().]{8,20}$/'],
            'email' => ['nullable', 'email', 'max:160'],
            'reservation_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:'.$this->businessTodayForRequest()],
            'reservation_time' => ['required', 'date_format:H:i'],
            'guests' => ['required', 'integer', 'min:1', 'max:40'],
            'table_id' => ['nullable', 'integer', Rule::exists('restaurant_tables', 'id')],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $date = $this->input('reservation_date');
            $time = $this->input('reservation_time');

            if (! $date || ! $time || $validator->errors()->has('reservation_date') || $validator->errors()->has('reservation_time')) {
                return;
            }

            $branch = Branch::query()->active()->find($this->input('branch_id'));
            if (! OpenDays::isOpenOn($date, $branch)) {
                $validator->errors()->add('reservation_date', 'Quán không nhận đặt bàn vào ngày đã chọn. Vui lòng chọn ngày mở cửa khác.');

                return;
            }

            $openingHours = OpeningHours::fromBranch($branch);

            if (! $openingHours->isWithin($time)) {
                $validator->errors()->add('reservation_time', $openingHours->message().' Vui lòng chọn giờ phù hợp.');

                return;
            }

            if ($openingHours->isPastToday($date, $time, $branch)) {
                $validator->errors()->add('reservation_time', 'Giờ đặt bàn đã qua. Nếu đặt trong hôm nay, vui lòng chọn giờ muộn hơn thời điểm hiện tại.');

                return;
            }

            $guests = (int) $this->input('guests');
            $branchId = (int) $this->input('branch_id');
            $durationMinutes = (int) setting('reservation_duration_minutes', ReservationTableAvailability::DEFAULT_DURATION_MINUTES);

            if ($this->filled('table_id')) {
                $table = RestaurantTable::query()->find($this->input('table_id'));

                if (! $table || (int) $table->branch_id !== $branchId) {
                    $validator->errors()->add('table_id', 'Bàn đã chọn không thuộc cơ sở này.');

                    return;
                }

                if (! ReservationTableAvailability::isTableAvailable($table, $date, $time, $guests, null, $durationMinutes)) {
                    $validator->errors()->add('table_id', 'Bàn đã chọn không còn trống hoặc không đủ số ghế.');
                }

                return;
            }

            if (! ReservationTableAvailability::bestAvailableTable($branchId, $date, $time, $guests, null, $durationMinutes)) {
                $validator->errors()->add('table_id', 'Hiện không còn bàn phù hợp trong khung giờ này. Vui lòng chọn giờ khác hoặc liên hệ Paprika.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập họ tên.',
            'branch_id.required' => 'Vui lòng chọn cơ sở.',
            'branch_id.exists' => 'Cơ sở đã chọn không hợp lệ hoặc đang tạm ẩn.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại chưa đúng định dạng.',
            'email.email' => 'Email chưa đúng định dạng.',
            'reservation_date.required' => 'Vui lòng chọn ngày đặt bàn.',
            'reservation_date.date_format' => 'Ngày đặt bàn chưa đúng định dạng.',
            'reservation_date.after_or_equal' => 'Ngày đặt bàn không được ở quá khứ.',
            'reservation_time.required' => 'Vui lòng chọn giờ đặt bàn.',
            'reservation_time.date_format' => 'Giờ đặt bàn chưa đúng định dạng.',
            'guests.required' => 'Vui lòng nhập số người.',
            'guests.min' => 'Số người tối thiểu là :min.',
            'guests.max' => 'Số người tối đa là :max.',
        ];
    }

    private function businessTodayForRequest(): string
    {
        $branch = Branch::query()->active()->find($this->input('branch_id'));

        return business_today($branch)->toDateString();
    }
}
