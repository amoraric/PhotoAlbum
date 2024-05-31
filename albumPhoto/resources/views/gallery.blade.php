@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Create Album</h2>
    <form action="{{ route('albums.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="albumName">Album Name</label>
            <input type="text" class="form-control" id="albumName" name="album_name" required>
        </div>
        <button type="submit" class="btn btn-primary">Create Album</button>
    </form>

    <hr>

    <h2>Upload Photo</h2>
    <form action="{{ route('photos.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="albumSelect">Select Album</label>
            <select class="form-control" id="albumSelect" name="album_id" required>
                @foreach($albums as $album)
                <option value="{{ $album->id }}">{{ $album->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="photoName">Photo Name</label>
            <input type="text" class="form-control" id="photoName" name="photo_name" required>
        </div>
        <div class="form-group">
            <label for="photoFile">Upload Photo</label>
            <input type="file" class="form-control-file" id="photoFile" name="photo" accept="image/*" required>
        </div>
        <button type="submit" class="btn btn-primary">Upload Photo</button>
    </form>

    <hr>

    <h2>Gallery</h2>
    <div class="row">
        @foreach($albums as $album)
        <div class="col-md-6">
            <h3>{{ $album->name }}</h3>
            <div class="row mb-2">
                <div class="col-12 text-end">
                    <button class="btn btn-secondary" type="button" data-bs-toggle="modal" data-bs-target="#shareModalAlbum" @click="setAlbumId({{ $album->id }})">Share Album</button>
                </div>
            </div>
            <div class="row">
                @foreach($album->photos as $photo)
                <div class="col-md-4 position-relative">
                    <img src="{{ asset('storage/' . $photo->temp_path) }}" alt="{{ $photo->photo_name }}" class="img-thumbnail" data-bs-toggle="modal" data-bs-target="#imageModal" @click="showImage('{{ asset('storage/' . $photo->filename) }}')">
                    <button class="btn btn-secondary position-absolute bottom-0 start-0 m-1" type="button" data-bs-toggle="modal" data-bs-target="#shareModalPhotos" @click="setPhotoId({{ $photo->id }})">Share Photo</button>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Image Modal -->
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

<!-- Share Modals -->
<share-modal-photos></share-modal-photos>
<share-modal-album></share-modal-album>
@endsection
