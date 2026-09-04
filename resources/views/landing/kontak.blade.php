@extends('layouts.landing')

@section('content')

@include('landing.partials._navbar')

{{-- Page Hero --}}
<section class="page-hero">
    <div class="container">
        <div class="eyebrow">Hubungi Kami</div>
        <h1>Ada pertanyaan? <span style="color:var(--grass2)">Kami siap membantu.</span></h1>
        <p>Sampaikan pertanyaan, saran, atau kebutuhan Anda. Tim LadangWohIjo akan merespons secepatnya.</p>
    </div>
</section>

{{-- Contact Section --}}
<section class="section">
    <div class="container">
        <div class="contact-grid">

            {{-- Form --}}
            <div>
                <div class="eyebrow">Kirim pesan</div>
                <h2 style="font-size:clamp(22px,3vw,34px);letter-spacing:-.04em;margin-bottom:28px">Ceritakan apa yang Anda butuhkan.</h2>

                <form action="#" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="nama">Nama Lengkap</label>
                            <input class="form-input" type="text" id="nama" name="nama" placeholder="Nama Anda" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input class="form-input" type="email" id="email" name="email" placeholder="email@anda.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="subjek">Subjek</label>
                        <select class="form-select" id="subjek" name="subjek">
                            <option value="">— Pilih topik —</option>
                            <option value="demo">Request Demo / Presentasi</option>
                            <option value="fitur">Pertanyaan Fitur</option>
                            <option value="teknis">Bantuan Teknis</option>
                            <option value="kerjasama">Kerjasama &amp; Partnership</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="pesan">Pesan</label>
                        <textarea class="form-textarea" id="pesan" name="pesan" placeholder="Tuliskan pertanyaan atau kebutuhan Anda di sini..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:14px">
                        Kirim Pesan →
                    </button>

                    <p style="font-size:12px;color:#5a6a5a;margin-top:12px;text-align:center">
                        Kami biasanya membalas dalam 1–2 hari kerja.
                    </p>
                </form>
            </div>

            {{-- Info Kontak --}}
            <div class="contact-info-panel">
                <div class="eyebrow" style="margin-bottom:4px">Informasi kontak</div>
                <h3 style="font-size:clamp(18px,2.5vw,26px);letter-spacing:-.03em;margin-bottom:16px">Atau hubungi kami langsung.</h3>

                <div class="contact-info-card">
                    <div class="icon">💬</div>
                    <div>
                        <h4>WhatsApp</h4>
                        <p>Respon cepat melalui WhatsApp Business.</p>
                        <a href="https://wa.me/6281234567890" target="_blank" rel="noopener">+62 812-3456-7890</a>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div class="icon">📧</div>
                    <div>
                        <h4>Email</h4>
                        <p>Untuk pertanyaan formal dan kerjasama.</p>
                        <a href="mailto:hello@ladangwohijo.com">hello@ladangwohijo.com</a>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div class="icon">📍</div>
                    <div>
                        <h4>Lokasi</h4>
                        <p>LadangWohIjo — Operasional Greenhouse</p>
                        <p>Jawa Tengah, Indonesia</p>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div class="icon">🕐</div>
                    <div>
                        <h4>Jam Respons</h4>
                        <p>Senin – Sabtu, 08.00 – 17.00 WIB</p>
                        <p style="margin-top:4px">Hari libur nasional: respons mungkin lebih lambat.</p>
                    </div>
                </div>

                {{-- FAQ singkat --}}
                <div style="padding:22px;border:1px solid var(--line);border-radius:16px;background:rgba(18,30,21,.6)">
                    <h4 style="font-size:14px;margin-bottom:14px;color:var(--grass2)">FAQ Singkat</h4>
                    <div style="font-size:13px;color:var(--muted);display:grid;gap:10px">
                        <div>
                            <strong style="color:var(--cream)">Apakah AgroFlow gratis?</strong>
                            <p style="margin-top:3px">Platform ini sedang dalam tahap pengembangan aktif. Informasi harga akan diumumkan nanti.</p>
                        </div>
                        <div>
                            <strong style="color:var(--cream)">Bisa request demo langsung?</strong>
                            <p style="margin-top:3px">Bisa. Hubungi kami via WhatsApp atau isi form di sebelah kiri.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

@include('landing.partials._footer')

@endsection
