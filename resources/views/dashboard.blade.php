@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/dashboard.css') }}">
@endpush

@section('content')
    <div class="dashboard-page">
        <section class="dashboard-stats" aria-label="Ringkasan pemeriksaan hari ini">
            <article class="dashboard-stat dashboard-stat--scheduled">
                <div class="dashboard-stat__header">
                    <span class="dashboard-stat__icon" aria-hidden="true">
                        <i data-lucide="clipboard-list"></i>
                    </span>
                    <span class="dashboard-stat__label">Hari ini</span>
                </div>
                <div class="dashboard-stat__body">
                    <p>Pemeriksaan Hari Ini</p>
                    <strong>24</strong>
                    <span>Item terjadwal hari ini</span>
                </div>
            </article>

            <article class="dashboard-stat dashboard-stat--complete">
                <div class="dashboard-stat__header">
                    <span class="dashboard-stat__icon" aria-hidden="true">
                        <i data-lucide="circle-check-big"></i>
                    </span>
                    <span class="dashboard-stat__label">Sesuai</span>
                </div>
                <div class="dashboard-stat__body">
                    <p>Selesai</p>
                    <strong>20</strong>
                    <span>83% sudah dicek</span>
                </div>
            </article>

            <article class="dashboard-stat dashboard-stat--pending">
                <div class="dashboard-stat__header">
                    <span class="dashboard-stat__icon" aria-hidden="true">
                        <i data-lucide="clock-3"></i>
                    </span>
                    <span class="dashboard-stat__label">Tertunda</span>
                </div>
                <div class="dashboard-stat__body">
                    <p>Belum Dikerjakan</p>
                    <strong>4</strong>
                    <span>Menunggu pemeriksaan</span>
                </div>
            </article>

            <article class="dashboard-stat dashboard-stat--finding">
                <div class="dashboard-stat__header">
                    <span class="dashboard-stat__icon" aria-hidden="true">
                        <i data-lucide="triangle-alert"></i>
                    </span>
                    <span class="dashboard-stat__label">Perhatian</span>
                </div>
                <div class="dashboard-stat__body">
                    <p>Temuan</p>
                    <strong>1</strong>
                    <span>Perlu tindak lanjut</span>
                </div>
            </article>

            <article class="dashboard-stat dashboard-stat--overdue">
                <div class="dashboard-stat__header">
                    <span class="dashboard-stat__icon" aria-hidden="true">
                        <i data-lucide="timer-off"></i>
                    </span>
                    <span class="dashboard-stat__label">Overdue</span>
                </div>
                <div class="dashboard-stat__body">
                    <p>Terlambat</p>
                    <strong>0</strong>
                    <span>Tidak ada overdue</span>
                </div>
            </article>
        </section>

        <div class="dashboard-primary-grid">
            <section class="dashboard-panel dashboard-panel--inspection" aria-labelledby="today-inspection-title">
                <header class="dashboard-panel__header">
                    <div class="dashboard-panel__title">
                        <span class="dashboard-panel__icon" aria-hidden="true">
                            <i data-lucide="clipboard-check"></i>
                        </span>
                        <div>
                            <p>Checklist operasional</p>
                            <h2 id="today-inspection-title">Pemeriksaan Hari Ini</h2>
                        </div>
                    </div>
                    <button class="button button--secondary" type="button">
                        <i data-lucide="check-check" aria-hidden="true"></i>
                        Tandai Semua Sesuai
                    </button>
                </header>

                <div class="dashboard-machine">
                    <span class="dashboard-machine__icon" aria-hidden="true">
                        <i data-lucide="scan-line"></i>
                    </span>
                    <div>
                        <p>Mesin aktif</p>
                        <strong>Mesin X-Ray Baggage - SCP</strong>
                    </div>
                    <span class="status-badge status-badge--verified">
                        <i data-lucide="calendar-check-2" aria-hidden="true"></i>
                        Harian
                    </span>
                </div>

                <div class="dashboard-table-wrap">
                    <table class="dashboard-checklist-table">
                        <thead>
                            <tr>
                                <th scope="col">Kegiatan pemeriksaan</th>
                                <th scope="col">Kriteria / standar</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <span class="dashboard-item-index">01</span>
                                    Pemeriksaan lead curtain
                                </td>
                                <td>Utuh, lentur, dan tidak sobek</td>
                                <td>
                                    <span class="status-badge status-badge--success">
                                        <i data-lucide="check" aria-hidden="true"></i>
                                        Sesuai
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <span class="dashboard-item-index">02</span>
                                    Pemeriksaan conveyor belt
                                </td>
                                <td>Berjalan stabil dan tidak bergeser</td>
                                <td>
                                    <span class="status-badge status-badge--danger">
                                        <i data-lucide="x" aria-hidden="true"></i>
                                        Tidak sesuai
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <span class="dashboard-item-index">03</span>
                                    Leakage Radiation Test
                                </td>
                                <td>Nilai radiasi dalam batas aman</td>
                                <td>
                                    <span class="status-badge status-badge--success">
                                        <i data-lucide="check" aria-hidden="true"></i>
                                        Sesuai
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <span class="dashboard-item-index">04</span>
                                    Unit bagian luar
                                </td>
                                <td>Bersih dan tidak ada kerusakan fisik</td>
                                <td>
                                    <span class="status-badge status-badge--success">
                                        <i data-lucide="check" aria-hidden="true"></i>
                                        Sesuai
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <span class="dashboard-item-index">05</span>
                                    Monitor
                                </td>
                                <td>Tampilan jernih dan fungsi normal</td>
                                <td>
                                    <span class="status-badge status-badge--success">
                                        <i data-lucide="check" aria-hidden="true"></i>
                                        Sesuai
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <footer class="dashboard-panel__footer">
                    <span><strong>5</strong> item ditampilkan</span>
                    <span><strong>4</strong> sesuai &bull; <strong>1</strong> tidak sesuai</span>
                </footer>
            </section>

            <section class="dashboard-panel dashboard-panel--findings" aria-labelledby="latest-findings-title">
                <header class="dashboard-panel__header">
                    <div class="dashboard-panel__title">
                        <span class="dashboard-panel__icon dashboard-panel__icon--danger" aria-hidden="true">
                            <i data-lucide="shield-alert"></i>
                        </span>
                        <div>
                            <p>Monitoring tindak lanjut</p>
                            <h2 id="latest-findings-title">Temuan Terbaru</h2>
                        </div>
                    </div>
                </header>

                <div class="dashboard-findings-list">
                    <article class="dashboard-finding">
                        <span class="dashboard-finding__marker dashboard-finding__marker--open" aria-hidden="true"></span>
                        <div class="dashboard-finding__content">
                            <div>
                                <h3>Conveyor belt</h3>
                                <span class="status-badge status-badge--danger">Open</span>
                            </div>
                            <p>Pergerakan belt tidak stabil saat unit beroperasi.</p>
                            <small><i data-lucide="scan-line" aria-hidden="true"></i> X-Ray SCP &bull; Hari ini</small>
                        </div>
                    </article>

                    <article class="dashboard-finding">
                        <span class="dashboard-finding__marker dashboard-finding__marker--review" aria-hidden="true"></span>
                        <div class="dashboard-finding__content">
                            <div>
                                <h3>UPS fan</h3>
                                <span class="status-badge status-badge--pending">Review</span>
                            </div>
                            <p>Suara kipas perlu diperiksa oleh supervisor.</p>
                            <small><i data-lucide="scan-line" aria-hidden="true"></i> X-Ray HBS &bull; Kemarin</small>
                        </div>
                    </article>

                    <article class="dashboard-finding">
                        <span class="dashboard-finding__marker dashboard-finding__marker--normal" aria-hidden="true"></span>
                        <div class="dashboard-finding__content">
                            <div>
                                <h3>Lead curtain</h3>
                                <span class="status-badge status-badge--success">Normal</span>
                            </div>
                            <p>Pemeriksaan ulang selesai, kondisi kembali normal.</p>
                            <small><i data-lucide="scan-line" aria-hidden="true"></i> X-Ray SCP &bull; 2 hari lalu</small>
                        </div>
                    </article>
                </div>

                <button class="button button--neutral dashboard-findings-action" type="button">
                    Lihat Semua Temuan
                    <i data-lucide="arrow-right" aria-hidden="true"></i>
                </button>
            </section>
        </div>

        <section class="dashboard-panel dashboard-report" aria-labelledby="monthly-report-title">
            <header class="dashboard-panel__header">
                <div class="dashboard-panel__title">
                    <span class="dashboard-panel__icon" aria-hidden="true">
                        <i data-lucide="table-2"></i>
                    </span>
                    <div>
                        <p>Ringkasan periode Oktober 2026</p>
                        <h2 id="monthly-report-title">Preview Laporan Bulanan</h2>
                    </div>
                </div>
                <button class="button button--primary" type="button">
                    <i data-lucide="file-down" aria-hidden="true"></i>
                    Cetak PDF
                </button>
            </header>

            <div class="dashboard-report__legend" aria-label="Keterangan simbol laporan">
                <span><i class="dashboard-legend dashboard-legend--success" aria-hidden="true">&#10003;</i> Sesuai</span>
                <span><i class="dashboard-legend dashboard-legend--danger" aria-hidden="true">&times;</i> Tidak sesuai</span>
                <span><i class="dashboard-legend dashboard-legend--empty" aria-hidden="true">&minus;</i> Tidak dijadwalkan</span>
            </div>

            <div class="dashboard-report__table-wrap">
                <table class="dashboard-report-table">
                    <thead>
                        <tr>
                            <th scope="col">Kegiatan</th>
                            <th scope="col">Kriteria</th>
                            <th scope="col">1</th>
                            <th scope="col">2</th>
                            <th scope="col">3</th>
                            <th scope="col">4</th>
                            <th scope="col">5</th>
                            <th scope="col">6</th>
                            <th scope="col">7</th>
                            <th scope="col">Ket</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Lead curtain</td>
                            <td>Utuh dan tidak sobek</td>
                            <td><span class="dashboard-report-mark dashboard-report-mark--success" aria-label="Sesuai">&#10003;</span></td>
                            <td><span class="dashboard-report-mark dashboard-report-mark--success" aria-label="Sesuai">&#10003;</span></td>
                            <td><span class="dashboard-report-mark dashboard-report-mark--success" aria-label="Sesuai">&#10003;</span></td>
                            <td><span class="dashboard-report-mark dashboard-report-mark--success" aria-label="Sesuai">&#10003;</span></td>
                            <td><span class="dashboard-report-mark dashboard-report-mark--success" aria-label="Sesuai">&#10003;</span></td>
                            <td><span class="dashboard-report-mark dashboard-report-mark--empty" aria-label="Tidak dijadwalkan">&minus;</span></td>
                            <td><span class="dashboard-report-mark dashboard-report-mark--empty" aria-label="Tidak dijadwalkan">&minus;</span></td>
                            <td><span class="dashboard-report-note dashboard-report-note--normal">Normal</span></td>
                        </tr>
                        <tr>
                            <td>Conveyor belt</td>
                            <td>Berjalan stabil</td>
                            <td><span class="dashboard-report-mark dashboard-report-mark--success" aria-label="Sesuai">&#10003;</span></td>
                            <td><span class="dashboard-report-mark dashboard-report-mark--success" aria-label="Sesuai">&#10003;</span></td>
                            <td><span class="dashboard-report-mark dashboard-report-mark--danger" aria-label="Tidak sesuai">&times;</span></td>
                            <td><span class="dashboard-report-mark dashboard-report-mark--success" aria-label="Sesuai">&#10003;</span></td>
                            <td><span class="dashboard-report-mark dashboard-report-mark--success" aria-label="Sesuai">&#10003;</span></td>
                            <td><span class="dashboard-report-mark dashboard-report-mark--empty" aria-label="Tidak dijadwalkan">&minus;</span></td>
                            <td><span class="dashboard-report-mark dashboard-report-mark--empty" aria-label="Tidak dijadwalkan">&minus;</span></td>
                            <td><span class="dashboard-report-note dashboard-report-note--attention">Tindak lanjut</span></td>
                        </tr>
                        <tr>
                            <td>Light barriers</td>
                            <td>Sensor merespons normal</td>
                            <td><span class="dashboard-report-mark dashboard-report-mark--success" aria-label="Sesuai">&#10003;</span></td>
                            <td><span class="dashboard-report-mark dashboard-report-mark--success" aria-label="Sesuai">&#10003;</span></td>
                            <td><span class="dashboard-report-mark dashboard-report-mark--success" aria-label="Sesuai">&#10003;</span></td>
                            <td><span class="dashboard-report-mark dashboard-report-mark--success" aria-label="Sesuai">&#10003;</span></td>
                            <td><span class="dashboard-report-mark dashboard-report-mark--success" aria-label="Sesuai">&#10003;</span></td>
                            <td><span class="dashboard-report-mark dashboard-report-mark--empty" aria-label="Tidak dijadwalkan">&minus;</span></td>
                            <td><span class="dashboard-report-mark dashboard-report-mark--empty" aria-label="Tidak dijadwalkan">&minus;</span></td>
                            <td><span class="dashboard-report-note dashboard-report-note--normal">Normal</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
