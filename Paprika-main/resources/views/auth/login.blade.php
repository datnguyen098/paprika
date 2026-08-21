<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập admin | Paprika</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-[#f1eadc] px-4">
    <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-xl ring-1 ring-emerald-900/10">
        <div class="text-center">
            <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-emerald-800 text-xl font-bold text-white">HS</div>
            <h1 class="mt-5 text-2xl font-bold text-emerald-950">Đăng nhập Admin</h1>
            <p class="mt-2 text-sm text-slate-500">Quản trị website Paprika</p>
        </div>

        @include('admin.partials.alert')

        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
            @csrf
            <div>
                <label for="email" class="form-label">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-input" required autofocus>
                @error('email') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="form-label">Mật khẩu</label>
                <input id="password" type="password" name="password" class="form-input" required>
                @error('password') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-emerald-700">
                Ghi nhớ đăng nhập
            </label>
            <button type="submit" class="btn-primary w-full justify-center">Đăng nhập</button>
        </form>
    </div>
</body>
</html>
