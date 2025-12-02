<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Add Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script type="module" src="/Misc/processes.js" defer></script>
</head>
<body x-data="loginForm()">
  <h2>Login</h2>
  <form @submit.prevent="submitForm">
      <label>Username:</label><br>
      <input type="text" x-model="username" name="username"><br>
      <label>Password:</label><br>
      <input type="password" x-model="password" name="password"><br><br>
      <button type="submit">Login</button>
  </form>
  <p>Don't have an account? <a href="register.php">Register here</a></p>
</body>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('loginForm', () => ({
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
        body: JSON.stringify({ action: 'login', username: this.username, password: this.password })
        });
        const data = await res.json();


            if (data.success) {
            Swal.fire('Success', data.message, 'success').then(() => {
                sessionStorage.setItem('userType', data.type);
                if (data.type === 1) window.location.href = '/Customer/index.php';
                else if (data.type === 2 || data.type === 3) window.location.href = '/Shared Admin Pages/index.php';
                else Swal.fire('Error', 'Unknown user type', 'error');
            });
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

</html>
