import './bootstrap';
import Alpine from 'alpinejs';

/**
 * Tooltip manager
 */
Alpine.store('tooltip', {
    isVisible: false,
    text: '',

    visible(e) {
        this.text = e.target.dataset.tooltip;
        this.isVisible = true;
    },

    hide() {
        this.isVisible = false;
        this.text = '';
    }
});

/**
 * Gallery manager
 */
Alpine.store('gallery', {
    list: [
        {hash: '90198', original_name: '90198.jpg'},
    ],

    current: null,

    async getList() {
        await window.axios.get('/api/gallery', {withCredentials: true})
        .then((response) => {
            if (Object.values(response.data).length > 0) {
                this.list = Object.values(response.data);
            }

            this.createThumbnails();
        })
        .catch((error) => {
            console.error('Error : ', error);
        });
    },

    createThumbnails() {
        this.list.map((thumb, item) => {
            let wrapper = document.createElement('div');
            let img = document.createElement('img');
            let extension = '.' + thumb.original_name.split('.')[1];

            wrapper.setAttribute('class', 'imageWrapper');
            wrapper.setAttribute('x-on:click', '$store.gallery.setCurrent(' + item + ')');

            img.setAttribute('class', 'thumbnail');
            img.setAttribute('src', 'uploads/' + thumb.hash + extension);
            img.setAttribute('alt', thumb.original_name)

            wrapper.appendChild(img);

            document.querySelector('#list').appendChild(wrapper);
        });

        this.setCurrent(0);
    },

    deleteCurrentDisplay() {
        const tag = document.querySelector('#currentImage');

        if (tag) tag.parentNode.removeChild(tag);
    },

    setCurrent(item) {
        const tag = document.createElement('img');
        let photo, extension;

        if (this.list.length > 0) {
            this.deleteCurrentDisplay();

            this.current = (item < this.list.length) ? this.list[item] : this.list[0];

            photo = this.current;
            extension = '.' + photo.original_name.split('.')[1];

            tag.setAttribute('src', 'uploads/' + photo.hash + extension);
            tag.setAttribute('alt', photo.original_name);
            tag.setAttribute('id', 'currentImage');

            document.querySelector('#gallery').appendChild(tag);
        }
    }
});

window.Alpine = Alpine;

Alpine.start();
