@extends('layouts.canvas')

@section('content')
<div class="container">
    <h2>Create Album</h2>
    <form action="{{ route('albums.store') }}" method="POST">
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
    <div class="gallery">
        @foreach($albums as $album)
            <h3>{{ $album->name }}</h3>
            @foreach($album->photos as $photo)
                <img src="{{ asset('storage/' . $photo->path) }}" alt="{{ $photo->name }}">
            @endforeach
        @endforeach
    </div>
</div>
@endsection
