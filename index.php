<!doctype html>
<html>

<?php
session_start();

if (isset($_SESSION["username"])) {
  header("Location: lobby.php");
  return;
}
?>

<head>
  <meta charset="utf-8" />
  <title>Robot Wars - Welcome!</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="style.css" />
  <script
    src="https://cdnjs.cloudflare.com/ajax/libs/htmx/1.9.9/htmx.min.js"
    integrity="sha512-FSS62yxqCRMCtm1J+ddRwX8DuCRVt/WMpihCo06P+Je5AG4CV9yoLX53zHaOB5w/eZdG7d/QAyUEJTnHZHrWKg=="
    crossorigin="anonymous"
    referrerpolicy="no-referrer">
  </script>
</head>

<body class="h-screen flex flex-col justify-center">
  <h1 class="text-5xl font-semibold text-center mb-4">
    Welcome to Robot Wars
  </h1>

  <div class="flex flex-row gap-5 justify-center font-mono">
    <div class="border rounded border-black px-5 py-5 justify-center">
      <h2 class="font-semibold text-center mb-4">Login</h2>
      <form hx-post="user/do_login.php" hx-swap="none" hx-indicator="#register-submit" name="login" class="w-full gap-2 grid">
        <label for="login-username">Username:</label>
        <input type="text" id="login-username" required name="username" class="border-2 px-2 border-blue-500 rounded-xl" />

        <label for="login-password">Password:</label>
        <input type="password"
               id="login-password"
               required
               name="password"
               class="border-2 px-2 border-blue-500 rounded-xl" />

        <button type="submit" id="login-submit" required name="login-submit" class="bg-orange-500 col-span-2 mx-5 py-2 rounded-lg hover:bg-orange-300 [&.htmx-request]:border-orange-700 [&.htmx-request]:pointer-events-none">
          Log In
        </button>
        <div id="login-error"></div>
      </form>
    </div>

    <div class="border rounded border-black px-5 py-5 justify-center">
      <h2 class="font-semibold text-center mb-4">Register</h2>

      <form hx-post="user/do_register.php" hx-swap="none" hx-indicator="#register-submit" name="register" class="w-full gap-1 grid">
        <label for="register-email">Email:</label>
        <input type="email" id="register-email" required name="email" class="border-2 px-2 border-blue-500 rounded-xl" />

        <label for="register-username">Username:</label>
        <input type="text" id="register-username" required name="username" class="border-2 px-2 border-blue-500 rounded-xl" />

        <label for="register-password">Password:</label>
        <input type="password" id="register-password" required name="password" class="border-2 px-2 border-blue-500 rounded-xl" />

        <label class="my-2 col-span-2">
          <input name="accept-tos" type="checkbox" required /> I agree with
          the
          <a href="#">Terms of Service</a>
        </label>

        <button type="submit" id="register-submit" name="register-submit" class="bg-orange-500 col-span-2 mx-5 py-2 rounded-lg hover:bg-orange-300 [&.htmx-request]:border-orange-700 [&.htmx-request]:pointer-events-none">
          Register Account
        </button>

        <div id="register-error"></div>
      </form>
    </div>
  </div>
</body>

</html>
