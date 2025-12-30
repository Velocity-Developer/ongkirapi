<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>KodePos</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body style="font-family:'Instrument Sans';background-color:#f4f4f4;">


    <div class="container mx-auto p-2">

        <div class="card">
            <div class="card-header bg-warning text-dark">
                KodePos tidak ditemukan
            </div>
            <div class="card-body">

                {{-- Search --}}
                <form method="GET" class="row g-2 mb-3 justify-content-end">
                    <div class="col-md-4">
                        <input type="text" name="q" value="{{ $search }}"
                            class="form-control" placeholder="Cari kode pos...">
                    </div>

                    <div class="col-md-2">
                        <select name="per_page" class="form-select">
                            @foreach ([5,10,25,50] as $size)
                            <option value="{{ $size }}"
                                {{ $perPage == $size ? 'selected' : '' }}>
                                {{ $size }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            @foreach (['active','inactive'] as $size)
                            <option value="{{ $size }}"
                                {{ $status == $size ? 'selected' : '' }}>
                                {{ $size }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button class="btn btn-primary">Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr class="table-dark">
                                <th>Kode Pos</th>
                                <th>Status</th>
                                <th>Note</th>
                                <th>Cerated</th>
                            </tr>
                        <tbody>
                            @foreach ($kodepos as $item)
                            <tr>
                                <td>{{ $item->kode_pos }}</td>
                                <td>{{ $item->status }}</td>
                                <td class="small">{{ $item->note }}</td>
                                <td>{{ $item->created_at }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        </thead>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Menampilkan
                        {{ $kodepos->firstItem() ?? 0 }}
                        –
                        {{ $kodepos->lastItem() ?? 0 }}
                        dari {{ $kodepos->total() }} data
                    </div>

                    <div>
                        {{ $kodepos->links() }}
                    </div>
                </div>

            </div>
        </div>
    </div>

    <style>
        @media(min-width: 768px) {
            .container {
                max-width: 1100px;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>