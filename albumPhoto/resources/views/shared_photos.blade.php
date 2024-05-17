@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Shared Photos</h2>
    <div class="row">
    @foreach($sharedImages as $photo)
    <div class="col-md-4">
        <img src="{{ asset('storage/' . $photo->path) }}" alt="{{ $photo->photo_name }}" class="img-thumbnail" data-bs-toggle="modal" data-bs-target="#imageModal" onclick="showImage('{{ asset('storage/' . $photo->path) }}')">
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


@endsection