<?php

namespace App\Exports;

use App\Models\Reservation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReservationsExport implements FromArray, ShouldAutoSize, WithEvents
{
    /**
     * @param \Illuminate\Support\Collection<int, Reservation> $reservations
     */
    public function __construct(private readonly Collection $reservations) {}

    public function array(): array
    {
        return [
            ['Danh sách đặt bàn'],
            ['Xuất từ hệ thống quản trị'],
            $this->headings(),
            ...$this->reservations->map(fn (Reservation $reservation): array => $this->row($reservation))->all(),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(3, $this->reservations->count() + 3);

                $sheet->mergeCells('A1:K1');
                $sheet->mergeCells('A2:K2');
                $sheet->freezePane('A4');
                $sheet->setAutoFilter("A3:K{$lastRow}");

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true, 'color' => ['rgb' => '64748B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A3:K3')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '667132'],
                    ],
                ]);
            },
        ];
    }

    private function headings(): array
    {
        return [
            'Ngày đặt',
            'Giờ đặt',
            'Tên khách',
            'Điện thoại',
            'Email',
            'Cơ sở',
            'Số khách',
            'Ghi chú khách',
            'Ghi chú admin',
            'Trạng thái',
            'Ngày tạo',
        ];
    }

    private function row(Reservation $reservation): array
    {
        $reservation->loadMissing('branch');

        return [
            $reservation->reservation_date?->format('d/m/Y'),
            substr((string) $reservation->reservation_time, 0, 5),
            $reservation->name,
            $reservation->phone,
            $reservation->email,
            $reservation->branch?->name,
            $reservation->guests,
            $reservation->note,
            $reservation->admin_note,
            $reservation->statusLabel(),
            business_time($reservation->created_at, $reservation->branch)?->format('d/m/Y H:i'),
        ];
    }
}
