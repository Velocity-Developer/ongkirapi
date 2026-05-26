@extends('layouts.app')

@section('title', 'KodePos')

@section('content')

<div class="container mx-auto p-2" x-data="kodeposManager()">

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
                    </thead>
                    <tbody>
                        @forelse ($kodepos as $item)
                        <tr>
                            <td>{{ $kodepos->firstItem() + $loop->index }}</td>
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
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                Data kode pos tidak ditemukan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            {{ $kodepos->links('pagination::bootstrap-5') }}

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

@endsection