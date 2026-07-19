<template>
  <div class="max-w-2xl mx-auto my-12 px-4">
    <h1 class="font-display text-2xl font-bold mb-6">My Account</h1>

    <AccountNav />

    <div
      v-if="isDemoAccount"
      class="mb-6 p-4 rounded-lg bg-orange-50 border border-orange-200 text-sm text-gray-700"
    >
      You're signed in with the <span class="font-semibold">shared demo account</span> —
      profile details and password can't be changed, so it stays working for every visitor.
    </div>

    <!-- Profile details -->
    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
      <h2 class="text-lg font-semibold mb-4">Profile Details</h2>

      <div v-if="profileSuccess" class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm">
        Profile updated.
      </div>
      <div v-if="profileError" class="mb-4 text-red-500 text-sm">
        {{ profileError }}
      </div>

      <form @submit.prevent="saveProfile">
        <fieldset :disabled="isDemoAccount" class="space-y-4 disabled:opacity-60">
        <div>
          <label class="block mb-1 font-medium" for="name">Full Name</label>
          <input
            v-model="profileForm.name"
            id="name"
            type="text"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-orange-400"
          />
        </div>

        <div>
          <label class="block mb-1 font-medium" for="email">Email</label>
          <input
            v-model="profileForm.email"
            id="email"
            type="email"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-orange-400"
          />
        </div>

        <div>
          <label class="block mb-1 font-medium" for="phone">Phone</label>
          <input
            v-model="profileForm.phone"
            id="phone"
            type="tel"
            class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-orange-400"
            placeholder="09XXXXXXXXX"
          />
        </div>

        <div>
          <label class="block mb-1 font-medium" for="default_shipping_address">Default Shipping Address</label>
          <textarea
            v-model="profileForm.default_shipping_address"
            id="default_shipping_address"
            rows="3"
            class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-orange-400"
            placeholder="Street, Barangay, City, Province"
          ></textarea>
        </div>

        <button
          type="submit"
          :disabled="savingProfile"
          class="bg-orange-500 text-white px-6 py-2 rounded hover:bg-orange-600 transition disabled:opacity-50"
        >
          {{ savingProfile ? "Saving..." : "Save Changes" }}
        </button>
        </fieldset>
      </form>
    </div>

    <!-- Change password -->
    <div class="bg-white p-6 rounded-lg shadow-md">
      <h2 class="text-lg font-semibold mb-4">Change Password</h2>

      <div v-if="passwordSuccess" class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm">
        Password updated.
      </div>
      <div v-if="passwordError" class="mb-4 text-red-500 text-sm">
        {{ passwordError }}
      </div>

      <form @submit.prevent="savePassword">
        <fieldset :disabled="isDemoAccount" class="space-y-4 disabled:opacity-60">
        <div>
          <label class="block mb-1 font-medium" for="current_password">Current Password</label>
          <input
            v-model="passwordForm.current_password"
            id="current_password"
            type="password"
            required
            autocomplete="current-password"
            class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-orange-400"
          />
        </div>

        <div>
          <label class="block mb-1 font-medium" for="new_password">New Password</label>
          <input
            v-model="passwordForm.password"
            id="new_password"
            type="password"
            required
            minlength="8"
            autocomplete="new-password"
            class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-orange-400"
            placeholder="At least 8 characters"
          />
        </div>

        <div>
          <label class="block mb-1 font-medium" for="new_password_confirmation">Confirm New Password</label>
          <input
            v-model="passwordForm.password_confirmation"
            id="new_password_confirmation"
            type="password"
            required
            minlength="8"
            autocomplete="new-password"
            class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-orange-400"
          />
        </div>

        <button
          type="submit"
          :disabled="savingPassword"
          class="bg-orange-500 text-white px-6 py-2 rounded hover:bg-orange-600 transition disabled:opacity-50"
        >
          {{ savingPassword ? "Updating..." : "Update Password" }}
        </button>
        </fieldset>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useAuthStore } from "../../stores/auth";
import { updateProfile, changePassword, firstValidationError } from "../../services/account";
import { useDemoAccount } from "../../composables/useDemoAccount";
import AccountNav from "../../components/account/AccountNav.vue";

const auth = useAuthStore();
const { isDemoAccount } = useDemoAccount();

const profileForm = ref({
  name: auth.user?.name ?? "",
  email: auth.user?.email ?? "",
  phone: auth.user?.phone ?? "",
  default_shipping_address: auth.user?.default_shipping_address ?? "",
});
const savingProfile = ref(false);
const profileSuccess = ref(false);
const profileError = ref("");

const passwordForm = ref({
  current_password: "",
  password: "",
  password_confirmation: "",
});
const savingPassword = ref(false);
const passwordSuccess = ref(false);
const passwordError = ref("");

async function saveProfile() {
  if (isDemoAccount.value) return;
  profileSuccess.value = false;
  profileError.value = "";
  savingProfile.value = true;

  try {
    const updated = await updateProfile(profileForm.value);
    auth.setUser(updated);
    profileSuccess.value = true;
  } catch (e) {
    profileError.value = firstValidationError(e, "Could not save your profile.");
  } finally {
    savingProfile.value = false;
  }
}

async function savePassword() {
  if (isDemoAccount.value) return;
  passwordSuccess.value = false;
  passwordError.value = "";
  savingPassword.value = true;

  try {
    await changePassword(passwordForm.value);
    passwordSuccess.value = true;
    passwordForm.value = { current_password: "", password: "", password_confirmation: "" };
  } catch (e) {
    passwordError.value = firstValidationError(e, "Could not update your password.");
  } finally {
    savingPassword.value = false;
  }
}
</script>
