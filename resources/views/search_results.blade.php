<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pencarian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .search-container {
            max-width: 90%;
            margin: 2rem auto;
        }
        .card {
            transition: box-shadow .3s;
        }
        .card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,.1);
        }
        .card-title a {
            text-decoration: none;
            color: #212529;
        }
        .card-title a:hover {
            color: #0d6efd;
        }
        mark {
            padding: .1em .3em;
            background-color: #ffe680;
            border-radius: 4px;
        }
        .text-muted {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container search-container">
        <header class="text-center mb-4">
            <h1 class="display-5">🔎 Pencarian Adaptif Solr</h1>
            <p class="lead">Cari informasi yang Anda butuhkan dengan cepat.</p>
        </header>

        {{-- FORM PENCARIAN --}}
        <form action="search" method="POST" class="mb-5">
            @csrf
            <div class="input-group input-group-lg">
                {{-- TAMBAHAN: Dropdown untuk memilih field --}}
                <select class="form-select" name="field" style="max-width: 180px;">
                    <option value="all" {{ (isset($searchField) && $searchField == 'all') ? 'selected' : '' }}>Semua Field</option>
                    <option value="title" {{ (isset($searchField) && $searchField == 'title') ? 'selected' : '' }}>Judul</option>
                    <option value="author" {{ (isset($searchField) && $searchField == 'author') ? 'selected' : '' }}>Pengarang</option>
                    <option value="subject" {{ (isset($searchField) && $searchField == 'subject') ? 'selected' : '' }}>Subjek</option>
                    <option value="publisher" {{ (isset($searchField) && $searchField == 'publisher') ? 'selected' : '' }}>Penerbit</option>
                </select>
        
        <input type="text" name="q" class="form-control" placeholder="Masukkan kata kunci..." value="{{ $query ?? '' }}" required>
        <button class="btn btn-primary" type="submit">Cari</button>
            </div>
        </form>

        {{-- LAYOUT BARU DENGAN SIDEBAR --}}
        <div class="row">
            {{-- ==================== SIDEBAR STATISTIK/FACET ==================== --}}
            <div class="col-md-4">
                {{-- ... (Kode sidebar facet Anda sudah benar, tidak perlu diubah) ... --}}
                @if(isset($facets) && !empty($facets))
                    <h4>Statistik</h4>
                    {{-- FACET UNTUK KLASIFIKASI JENIS BAHAN --}}
                    @if(!empty($facets['worksheet_id']))
                        <div class="card mt-3">
                            <div class="card-header">Jenis Bahan</div>
                            <ul class="list-group list-group-flush">
                                @for ($i = 0; $i < count($facets['worksheet_id']); $i += 2)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $facets['worksheet_id'][$i] }}
                                        <span class="badge bg-primary rounded-pill">{{ $facets['worksheet_id'][$i+1] }}</span>
                                    </li>
                                @endfor
                            </ul>
                        </div>
                    @endif
                    {{-- FACET UNTUK KLASIFIKASI BESAR --}}
                    @if(!empty($facets['klasbesar']))
                        <div class="card mt-3">
                            <div class="card-header">Top Klasifikasi</div>
                            <ul class="list-group list-group-flush">
                                @for ($i = 0; $i < count($facets['klasbesar']); $i += 2)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $facets['klasbesar'][$i] }}
                                        <span class="badge bg-primary rounded-pill">{{ $facets['klasbesar'][$i+1] }}</span>
                                    </li>
                                @endfor
                            </ul>
                        </div>
                    @endif
                    {{-- FACET UNTUK AUTHOR --}}
                    @if(!empty($facets['author_s']))
                         <div class="card mt-3">
                            <div class="card-header">Pengarang</div>
                            <ul class="list-group list-group-flush">
                                @for ($i = 0; $i < count($facets['author_s']); $i += 2)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $facets['author_s'][$i] }}
                                        <span class="badge bg-primary rounded-pill">{{ $facets['author_s'][$i+1] }}</span>
                                    </li>
                                @endfor
                            </ul>
                        </div>
                    @endif
                    {{-- FACET UNTUK PENERBIT --}}
                    @if(!empty($facets['publisher_s']))
                         <div class="card mt-3">
                            <div class="card-header">Penerbit</div>
                            <ul class="list-group list-group-flush">
                                @for ($i = 0; $i < count($facets['publisher_s']); $i += 2)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $facets['publisher_s'][$i] }}
                                        <span class="badge bg-primary rounded-pill">{{ $facets['publisher_s'][$i+1] }}</span>
                                    </li>
                                @endfor
                            </ul>
                        </div>
                    @endif
                    {{-- FACET UNTUK Lokasi Terbitan --}}
                    @if(!empty($facets['publishlocation_s']))
                         <div class="card mt-3">
                            <div class="card-header">Lokasi Terbitan</div>
                            <ul class="list-group list-group-flush">
                                @for ($i = 0; $i < count($facets['publishlocation_s']); $i += 2)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $facets['publishlocation_s'][$i] }}
                                        <span class="badge bg-primary rounded-pill">{{ $facets['publishlocation_s'][$i+1] }}</span>
                                    </li>
                                @endfor
                            </ul>
                        </div>
                    @endif
                    {{-- FACET UNTUK Tahun Terbit --}}
                    @if(!empty($facets['publishyear']))
                         <div class="card mt-3">
                            <div class="card-header">Tahun Terbit</div>
                            <ul class="list-group list-group-flush">
                                @for ($i = 0; $i < count($facets['publishyear']); $i += 2)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $facets['publishyear'][$i] }}
                                        <span class="badge bg-primary rounded-pill">{{ $facets['publishyear'][$i+1] }}</span>
                                    </li>
                                @endfor
                            </ul>
                        </div>
                    @endif
                    {{-- FACET UNTUK SUBJECT--}}
                    @if(!empty($facets['subject_s']))
                         <div class="card mt-3">
                            <div class="card-header">Subjek</div>
                            <ul class="list-group list-group-flush">
                                @for ($i = 0; $i < count($facets['subject_s']); $i += 2)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $facets['subject_s'][$i] }}
                                        <span class="badge bg-primary rounded-pill">{{ $facets['subject_s'][$i+1] }}</span>
                                    </li>
                                @endfor
                            </ul>
                        </div>
                    @endif
                @endif
            </div>

            {{-- ==================== HASIL PENCARIAN UTAMA ==================== --}}
            <div class="col-md-8">
                @if(isset($results))
                    @if(!empty($query))
                        
                        {{-- Header Hasil Pencarian --}}
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">Hasil untuk: <mark>{{ $query }}</mark></h4>
                            @if($results->total() > 0)
                                <div>
                                    <span class="text-muted">{{ number_format($results->total()) }} hasil</span>
                                    @if(isset($searchTime) && $searchTime > 0)
                                        <small class="text-muted ms-2">({{ number_format($searchTime, 0) }} ms)</small>
                                    @endif
                                </div>
                            @endif
                        </div>

                        @if($results->isEmpty())
                            <div class="alert alert-warning text-center mt-4">
                                <h5>Oops! Tidak Ada Hasil Ditemukan</h5>
                                <p class="mb-0">Coba gunakan kata kunci yang berbeda atau lebih umum.</p>
                            </div>
                        @else
                            {{-- Daftar Hasil Pencarian --}}
                            <div class="list-results">
                                @foreach($results as $result)
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <a href="#">{{ $result->title ?? 'Judul tidak tersedia' }}</a>
                                            </h5>
                                             @if(isset($result->author_s))
                                            <h6 class="card-subtitle mb-3 text-muted">
                                                @if(count($result->author_s) > 0) 
                                                    @foreach($result->author_s as $au)
                                                       {{ $au }};
                                                    @endforeach
                                                
                                                @endif
                                            </h6>
                                            @endif
                                            <div class="card-text small">
                                                <div class="row">
                                                    {{-- Kolom Kiri --}}
                                                    <div class="col-md-6">
                                                        <strong>Penerbit:</strong> {{ $result->publisher ?? 'N/A' }} <br>
                                                        <strong>Lokasi Terbit:</strong> {{ $result->publishlocation ?? 'N/A' }} <br>
                                                        <strong>Tahun Terbit:</strong> {{ $result->publishyear ?? 'N/A' }} <br>
                                                    </div>
                                                    {{-- Kolom Kanan --}}
                                                    <div class="col-md-6">
                                                        <strong>DDC:</strong> {{ $result->dewey_no[0] ?? 'N/A' }} <br>
                                                       
                                                        {{-- Field Publikasi yang baru ditambahkan --}}
                                                        <strong>Publikasi:</strong> {{ $result->publikasi ?? 'N/A' }} <br>
                                                    </div>
                                                </div>

                                                {{-- Bagian Subjek yang diubah menjadi badge --}}
                                                <hr class="my-2">
                                                <strong>Klasifikasi:</strong> {{ $result->klasbesar ?? 'N/A' }} <br>
                                                <strong>Subjek:</strong>
                                                @if(!empty($result->subject_s))
                                                    @foreach($result->subject_s as $sub)
                                                        <span class="badge bg-secondary fw-normal me-1 mb-1">{{ $sub }}</span>
                                                    @endforeach
                                                @else
                                                    N/A
                                                @endif
                                            </div>
                                        </div>
                                        @if(isset($result->id))
                                        <div class="card-footer bg-transparent text-end text-muted small">
                                            BIB-ID: {{ $result->bibid }} 
                                        </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            {{-- Paginasi (HANYA DI SINI, SETELAH SEMUA HASIL) --}}
                            <div class="d-flex justify-content-center mt-4">
                                <div class="text-center">
                                    <p class="text-muted small">
                                        Menampilkan hasil {{ $results->firstItem() }} sampai {{ $results->lastItem() }} dari total {{ number_format($results->total()) }}
                                    </p>
                                    {{ $results->appends(['q' => $query, 'field' => $searchField])->links() }}
                                </div>
                            </div>
                        @endif
                    @else
                        {{-- Tampilan Awal --}}
                        <div class="text-center p-5 text-muted border rounded">
                            <p class="mb-0">Silakan gunakan form di atas untuk memulai pencarian.</p>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>