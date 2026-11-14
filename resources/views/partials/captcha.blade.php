{{-- Verifikasi anti-bot. Gambar dibuat server (PNG), jawaban hanya disimpan sebagai hash di sesi. --}}
<div>
    <label for="captcha" class="label">Kode verifikasi</label>
    <div class="flex items-center gap-2">
        <img src="{{ route('captcha') }}?r={{ \Illuminate\Support\Str::random(8) }}" alt="Kode verifikasi berupa gambar huruf dan angka" width="200" height="64" class="h-14 w-auto rounded-lg border border-line" data-captcha-img>
        <button type="button" class="btn btn-ghost size-11 shrink-0 rounded-lg p-0" data-captcha-refresh title="Ganti kode" aria-label="Ganti kode verifikasi">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.64-6.36L21 8"/><path d="M21 3v5h-5"/></svg>
        </button>
    </div>
    <input id="captcha" name="captcha" class="input mt-2 uppercase tracking-[0.3em]" autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="10" required placeholder="Kode di atas">
    @error('captcha')<p class="error">{{ $message }}</p>@enderror
</div>
