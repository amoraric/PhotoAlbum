import './bootstrap';
import 'bootstrap';
import { createApp } from 'vue';
import ExampleComponent from './components/ExampleComponent.vue';
import ShareModalPhotos from './components/ShareModalPhotos.vue';
import ShareModalAlbum from './components/ShareModalAlbum.vue'; // Nouvelle importation
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import * as bootstrap from 'bootstrap';
import $ from 'jquery';
import 'lightbox2/dist/css/lightbox.min.css';
import 'lightbox2/dist/js/lightbox.min.js';

const app = createApp({
    data() {
        return {
            photoId: null,
            albumId: null, // Ajout pour l'albumId
        };
    },
    methods: {
        showImage(src) {
            document.getElementById('modalImage').src = src;
        },
        setPhotoId(photoId) {
            this.photoId = photoId;
        },
        setAlbumId(albumId) { // Méthode pour définir l'albumId
            this.albumId = albumId;
        }
    }
});

app.component('example-component', ExampleComponent);
app.component('share-modal-photos', ShareModalPhotos);
app.component('share-modal-album', ShareModalAlbum); // Enregistrement du nouveau composant

app.mount('#app');

window.bootstrap = bootstrap;

document.addEventListener('DOMContentLoaded', function() {
    $('#shareModalPhotos').modal({
        backdrop: 'static',
        keyboard: false
    });

    $('#shareModalAlbum').modal({
        backdrop: 'static',
        keyboard: false
    });
});
