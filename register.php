<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Add Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Your existing script (will be converted later) -->
    <script type="module" src="/Misc/processes.js" defer></script>
</head>
<body>

<h2>Register</h2>

<form x-data="registerForm" @submit.prevent="submitForm">
    <label>Username:</label><br>
    <input type="text" x-model="username"><br>

    <label>Password:</label><br>
    <input type="password" x-model="password"><br><br>

    <button type="submit">Register</button>
</form>

<p>Already have an account? <a href="login.php">Login here</a></p>

<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('registerForm', () => ({
    username: '',
    password: '',
    async submitForm() {
      if (!this.username.trim() || !this.password.trim()) {
        return Swal.fire('Error', 'Please fill all fields', 'error');
      }
      try {
        const res = await fetch('Misc/handleforms.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'register', username: this.username, password: this.password })
        });
        const data = await res.json();
        if (data.success) {
          Swal.fire('Success', data.message, 'success');
          this.username = '';
          this.password = '';
        } else {
          Swal.fire('Error', data.message, 'error');
        }
      } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Something went wrong.', 'error');
      }
    }
  }));
});

</script>

</body>
</html>
