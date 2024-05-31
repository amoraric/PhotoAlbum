@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Shared Photos</h2>
    <div class="row">
    @foreach($sharedImages as $photo)
    <div class="col-md-4">
        <img src="{{ asset('storage/' . $photo->temp_path) }}" alt="{{ $photo->photo_name }}" class="img-thumbnail" data-bs-toggle="modal" data-bs-target="#imageModal" onclick="showImage('{{ asset('storage/' . $photo->path) }}')">
        <button class="btn btn-secondary mt-2" type="button" onclick="showShareList({{ $photo->id }}, 'photo')">Shared With</button>
    </div>
    @endforeach
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Image Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <img id="modalImage" src="" alt="Image" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<!-- Share List Modal -->
<div class="modal fade" id="shareListModal" tabindex="-1" aria-labelledby="shareListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shareListModalLabel">Shared With</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul id="shareList" class="list-group">
                    <!-- Shared users will be dynamically populated here -->
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Add the JavaScript functions to handle image click and show shared users -->
<script>
function showImage(src) {
    document.getElementById('modalImage').src = src;
}

function showShareList(id, type) {
    let url;
    if (type === 'album') {
        url = `/albums/${id}/share-list`;
    } else if (type === 'photo') {
        url = `/photos/${id}/share-list`;
    }
    fetch(url)
        .then(response => response.json())
        .then(data => {
            const shareList = document.getElementById('shareList');
            shareList.innerHTML = '';
            data.forEach(user => {
                const li = document.createElement('li');
                li.className = 'list-group-item';
                li.textContent = user.email;
                shareList.appendChild(li);
            });
            const shareListModal = new bootstrap.Modal(document.getElementById('shareListModal'));
            shareListModal.show();
        });
}
</script>
@endsection
