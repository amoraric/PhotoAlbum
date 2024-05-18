<template>
  <div class="modal fade" id="shareModalAlbum" tabindex="-1" aria-labelledby="shareModalAlbumLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="shareModalAlbumLabel">Share Album Options</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="d-grid gap-2">
            <button class="btn btn-primary" @click="showShareForm">Share</button>
            <button class="btn btn-warning" @click="showUnshareForm">Unshare</button>
            <button class="btn btn-info" @click="showShareList">View Share List</button>
          </div>
          <div v-if="showForm">
            <form :action="formAction" method="POST">
              <input type="hidden" name="_token" :value="csrfToken">
              <div class="form-group" v-if="shareAction">
                <label for="shareWith">Share with (user email):</label>
                <input type="email" class="form-control" id="shareWith" name="shareWith" required>
              </div>
              <button type="submit" class="btn btn-primary mt-3">{{ buttonText }}</button>
            </form>
          </div>
          <div v-if="showList">
            <h5>Users this album is shared with:</h5>
            <ul>
              <li v-for="user in shareList" :key="user.email">{{ user.email }}</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      showForm: false,
      showList: false,
      shareAction: true,
      buttonText: '',
      formAction: '',
      csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      shareList: [],
    };
  },
  methods: {
    showShareForm() {
      this.showForm = true;
      this.showList = false;
      this.shareAction = true;
      this.buttonText = 'Share';
      this.formAction = `/albums/${this.$root.albumId}/share`;
    },
    showUnshareForm() {
      this.showForm = true;
      this.showList = false;
      this.shareAction = false;
      this.buttonText = 'Unshare';
      this.formAction = `/albums/${this.$root.albumId}/unshare`;
    },
    showShareList() {
      this.showForm = false;
      this.showList = true;
      fetch(`/albums/${this.$root.albumId}/share-list`)
        .then(response => response.json())
        .then(data => {
          this.shareList = data;
        });
    },
    resetShareList() {
      this.shareList = [];
      this.showForm = false;
      this.showList = false;
    }
  },
  mounted() {
    const modalElement = document.getElementById('shareModalAlbum');
    modalElement.addEventListener('hidden.bs.modal', this.resetShareList);
  },
  beforeDestroy() {
    const modalElement = document.getElementById('shareModalAlbum');
    modalElement.removeEventListener('hidden.bs.modal', this.resetShareList);
  }
};
</script>
