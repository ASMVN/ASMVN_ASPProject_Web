<?php
 include ("connection.php");

?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Page</title>
    <link href="css_index.css" rel="stylesheet" crossorigin="anonymous">
  </head>
  <body>
  <section class="vh-100">
    
  <div class="container-fluid h-custom">
    <div class="row d-flex justify-content-center align-items-center h-100">
    <center>
      <h1>Đăng Nhập</h1>
    </center>
     <?php
    session_start();
    if (!empty($_SESSION['error'])) {
      echo '<div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4">'
           . htmlspecialchars($_SESSION['error'])
           . '</div>';
      unset($_SESSION['error']);
    }
    ?>

      <div class="col-md-9 col-lg-6 col-xl-5">
        <img src="https://th.bing.com/th/id/OIP.nfEpFO2ufpleMYdZ8aFwGgHaFS?rs=1&pid=ImgDetMain"
          class="img-fluid" alt="Sample image">
      </div>
      <div class="col-md-8 col-lg-6 col-xl-4 offset-xl-1">
      <form action="loginaction.php" method="post">
          <!-- Account input -->
          <div data-mdb-input-init class="form-outline mb-4">
            <label for="Member_ID" class="form-label" >User Name</label>
            <input type="input" id="Member_ID" name="Member_ID" class="form-control form-control-lg"
              placeholder="Enter your user name" />
          </div>

          <!-- Password input -->
          <div data-mdb-input-init class="form-outline mb-3">
          <label for="password" class="form-label" >Password</label>
            <input type="password" id="password" name="password" class="form-control form-control-lg"
              placeholder="Enter password" />
          </div>
          <div class="d-flex justify-content-between align-items-center">
          <div class="text-center text-lg-start mt-4 pt-2">
            <button  type="submit" data-mdb-button-init data-mdb-ripple-init class="btn btn-primary btn-lg"
              style="padding-left: 2.5rem; padding-right: 2.5rem;" id="logbtn" name="logbtn">Login</button>
            <p class="small fw-bold mt-2 pt-1 mb-0">Don't have an account? Please contact IT Department!</p>
          </div>

        </form>
      </div>
    </div>
  </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  </body>
</html>