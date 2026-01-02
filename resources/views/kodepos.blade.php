<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KodePos</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body style="font-family:'Instrument Sans';background-color:#f4f4f4;" x-data="kodeposManager()">

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
                            @foreach ([5,10,25,50,100,500] as $size)
                            <option value="{{ $size }}"
                                {{ $perPage == $size ? 'selected' : '' }}>
                                {{ $size }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">Semua</option>
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
                                <th>No</th>
                                <th>Kode Pos</th>
                                <th>Status</th>
                                <th>Subdistric V1</th>
                                <th>Subdistric V2</th>
                                <th>ZIP Code</th>
                                <th>Note</th>
                                <th>Created</th>
                                <th class="d-none">Aksi</th>
                            </tr>
                        <tbody>
                            @foreach ($kodepos as $item)
                            <tr>
                                <td>{{ $loop->iteration}}</td>
                                <td>{{ $item->kode_pos }}</td>
                                <td>
                                    @if($item->status && $item->status == 'active')
                                    <span class="badge bg-success">{{ $item->status }}</span>
                                    @else
                                    <span class="badge bg-secondary">{{ $item->status }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->subdistrict)
                                    {{ $item->subdistrict->subdistrict_name }}, {{ $item->subdistrict->type }} {{ $item->subdistrict->city }}, {{ $item->subdistrict->province }}
                                    @endif
                                </td>
                                <td>
                                    @if($item->rajaongkir_sub_district)
                                    {{ $item->rajaongkir_sub_district->name }}
                                    @endif
                                </td>
                                <td>
                                    @if($item->rajaongkir_sub_district)
                                    {{ $item->rajaongkir_sub_district->zip_code }}
                                    @endif
                                </td>
                                <td class="small">{{ $item->note }}</td>
                                <td>{{ $item->created_at }}</td>
                                <td class="d-none">
                                    <button
                                        class="btn btn-sm btn-primary"
                                        @click="editItem({
                                            id: {{ $item->id }},
                                            kode_pos: '{{ $item->kode_pos }}',
                                            status: '{{ $item->status }}',
                                            note: '{{ $item->note }}'
                                        })"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editModal">
                                        Edit
                                    </button>
                                </td>
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

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Kode Pos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="editKodePos" class="form-label">Kode Pos</label>
                            <input type="text" class="form-control" id="editKodePos" x-model="editForm.kode_pos">
                        </div>
                        <div class="mb-3">
                            <label for="editStatus" class="form-label">Status</label>
                            <select class="form-select" id="editStatus" x-model="editForm.status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editNote" class="form-label">Note</label>
                            <textarea class="form-control" id="editNote" rows="3" x-model="editForm.note"></textarea>
                        </div>
                        <input type="hidden" x-model="editForm.id">
                        @csrf
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" @click="saveItem()">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media(min-width: 768px) {
            .container {
                max-width: 1200px;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        function kodeposManager() {
            return {
                editForm: {
                    id: '',
                    kode_pos: '',
                    status: '',
                    note: ''
                },

                editItem(item) {
                    this.editForm = {
                        ...item
                    };
                    const modal = new bootstrap.Modal(document.getElementById('editModal'));
                    modal.show();
                },

                saveItem() {
                    // Di sini Anda bisa menambahkan logika untuk menyimpan data
                    // Misalnya mengirim data ke backend via AJAX/fetch
                    console.log('Saving item:', this.editForm);

                    // Contoh implementasi AJAX:
                    fetch(`/kodepos/update/${this.editForm.id}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                            },
                            body: JSON.stringify(this.editForm)
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log('Success:', data);
                            // Tutup modal
                            const modal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
                            modal.hide();
                            // Reload halaman untuk melihat perubahan
                            window.location.reload();
                        })
                        .catch((error) => {
                            console.error('Error:', error);
                            alert('Terjadi kesalahan saat menyimpan data');
                        });
                }
            }
        }
    </script>
</body>

</html>