<template>
  <loading-view :loading="loading">
    <heading class="mb-6">Two Factor Authentication</heading>
    <loading-card :loading="loading" class="card" style="max-width: 600px">
      <div class="p-3">
        <p class="p-2">
          Two Factor Authentication (2FA) strengthens access security by
          requiring two methods (also referred to as factors) to verify your
          identity. Two factor authentication protects against phishing, social
          engineering and password brute force attacks and secures your logins
          from attackers exploiting weak or stolen credentials.
        </p>
        <div v-if="status.confirmed != 1">
          <h3 class="p-2 mt-4">Recovery code</h3>
          <p class="p-2">
            Your recovery code is used to access your account in the event you
            cannot receive two-factor authentication codes.
          </p>
          <p class="p-2 no-print">
            <strong>
              1) Download, print or copy your code before continuing the 2FA
              setup.
            </strong>
          </p>

          <div class="text-center my-2 p-3 rec-box text-red-900">
            <h2>{{ twofa.recovery }}</h2>
            <a
              @click.prevent="
                downloadAsText('recover_code.txt', twofa.recovery)
              "
              href="#"
              >Download</a
            >
          </div>

          <div class="p-3">
            <p>
              <strong
                >2) Scan this QR code using your authenticator app to setup &
                enter OTP to activate 2FA</strong
              >
            </p>
            <div class="text-center">
              <img width="150" :src="twofa.google2fa_url" alt="qr_code" />
            </div>
            <br />
            <input
              v-model="form.otp"
              @keyup="autoSubmit()"
              placeholder="Enter OTP here"
              type="text"
              class="w-full form-control form-input form-input-bordered mb-2"
            />
            <button
              @click="confirmOtp"
              class="btn btn-default btn-primary mt-2"
            >
              Activate 2FA
            </button>
          </div>
        </div>
        <div v-else>
          <h3 class="p-2 mt-4 text-success">2FA is enabled</h3>
        </div>
      </div>
    </loading-card>
  </loading-view>
</template>

<script>
export default {
  data() {
    return {
      twofa: [],
      form: {},
      status: null,
      loading: true,
    };
  },

  mounted() {
    this.getStatus();
    this.getRecoveryCodes();
  },

  methods: {
    getStatus() {
      Nova.request()
        .get("/nova-vendor/nova-two-factor/status")
        .then((res) => {
          this.status = res.data;
          this.loading = false;
        });
    },

    getRecoveryCodes() {
      Nova.request()
        .get("/nova-vendor/nova-two-factor/register")
        .then((res) => {
          this.twofa = res.data;
        });
    },

    toggle() {
      Nova.request()
        .post("/nova-vendor/nova-two-factor/toggle", {
          status: this.status.enabled,
        })
        .then((res) => {
          this.$toasted.show(res.data.message, { type: "success" });
        });
    },

    confirmOtp() {
      Nova.request()
        .post("/nova-vendor/nova-two-factor/confirm", this.form)
        .then((res) => {
          this.$toasted.show(res.data.message, { type: "success" });
          this.getStatus();
        })
        .catch((err) => {
          this.$toasted.show(err.response.data.message, { type: "error" });
        });
    },

    autoSubmit() {
      if (this.form.otp.length === 6) {
        this.confirmOtp();
      }
    },
    downloadAsText(filename, text) {
      var element = document.createElement("a");
      element.setAttribute(
        "href",
        "data:text/plain;charset=utf-8," + encodeURIComponent(text)
      );
      element.setAttribute("download", filename);

      element.style.display = "none";
      document.body.appendChild(element);

      element.click();

      document.body.removeChild(element);
    },
  },
};
</script>
