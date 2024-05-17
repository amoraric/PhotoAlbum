<!-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photo Gallery</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/styles.css') }}" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div id="content">
            @yield('content')
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
</body>
</html> -->

<!-- resources/views/layouts/canvas.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Photo Album Gallery</title>
  <link href="{{ asset('css/style.css') }}" rel="stylesheet">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

</head>
<body>
  <div>
    <button onclick="window.location.href=' {{ route('shared_albums') }}' ">Go to Shared Albums</button>
    <button style="float: right;" onclick="logout()">Logout</button>
  </div>
  <div>
    @yield('content')
  </div>
  @include('partials.share_popup')
 <script>
  /*
    function addPhoto() {
      var formData = new FormData();
      formData.append('photo', $('#photoInput').prop('files')[0]);

      $.ajax({
        url: '{{ route('photos.store') }}',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
          if (response.success) {
            alert('Photo added successfully!');
            // Refresh or update the photo gallery as needed
          }
        },
        error: function() {
          alert('Error adding photo.');
        }
      });
    }

    function addAlbum() {
      var formData = new FormData();
      formData.append('cover_image', $('#albumInput').prop('files')[0]);
      formData.append('title', $('#albumTitle').val());

      $.ajax({
        url: '{{ route('albums.store') }}',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
          if (response.success) {
            alert('Album added successfully!');
            // Refresh or update the album gallery as needed
          }
        },
        error: function() {
          alert('Error adding album.');
        }
      });
    }

    function showPopup(type, id) {
      // AJAX to fetch sharing info and show popup
      $.ajax({
        url: `/api/share/${type}/${id}`,
        success: function(data) {
          $(`#${type}Popup`).show().find('.content').html(data);
        }
      });
    }

    function logout() {
      // Implement logout logic
    }*/
  </script>
</body>
</html>
