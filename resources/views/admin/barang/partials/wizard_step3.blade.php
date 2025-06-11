{{-- Step 3: Input Nomor Seri --}}
<div id="serial-number-inputs-container" class="mb-3">
    <p class="text-muted text-center">Input nomor seri akan muncul di sini setelah Anda mengisi "Jumlah Unit Awal" di
        Langkah 2 dan melanjutkan ke langkah ini.</p>
</div>

<button type="button" class="btn btn-info btn-sm mt-2 mb-3" id="suggestSerialsButton">
    <i class="bx bx-bulb me-1"></i> Sarankan Nomor Seri
</button>

@if ($errors->has('serial_numbers') || $errors->has('serial_numbers.*'))
    <div class="alert alert-danger py-2">
        @if ($errors->has('serial_numbers'))
            <p class="mb-1">{{ $errors->first('serial_numbers') }}</p>
        @endif
        @foreach ($errors->get('serial_numbers.*') as $key => $message)
            <p class="mb-1">Nomor Seri Unit {{ (int) explode('.', $key)[1] + 1 }}: {{ $message[0] }}</p>
        @endforeach
    </div>
@endif

<ul class="pager wizard twitter-bs-wizard-pager-link mt-3">
    <li class="previous"><a href="javascript:void(0);" class="btn btn-primary"><i class="bx bx-chevron-left me-1"></i>
            Kembali</a></li>
    <li class="finish float-end"><a href="javascript:void(0);" class="btn btn-success">Simpan Keseluruhan Data <i
                class="bx bx-save ms-1"></i></a></li>
</ul>
