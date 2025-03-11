<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input_email = $_POST['email'];
    $input_password = $_POST['password'];

    // Database connection
    $servername = "127.0.0.1";
    $username = "root";
    $password = ""; 
    $dbname = "employeedatabase";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Corrected column name to 'password'
    $stmt = $conn->prepare("SELECT password FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $input_email);
    $stmt->execute();
    $stmt->bind_result($stored_hash);
    $stmt->fetch();
    $stmt->close();
    $conn->close();

    if (password_verify($input_password, $stored_hash)) {
        // Corrected session variable name
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin.php");
        exit();
    } else {
        echo "Invalid email or password";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/3.10.2/mdb.min.css">
    <style>
        .gradient-custom-2 {
            background: #0d47a1;
            background: -webkit-linear-gradient(to right, #0d47a1, #1976d2);
            background: linear-gradient(to right, #0d47a1, #1976d2);
        }

        @media (min-width: 768px) {
            .gradient-form {
                height: 100vh !important;
            }
        }

        @media (min-width: 769px) {
            .gradient-custom-2 {
                border-top-right-radius: .3rem;
                border-bottom-right-radius: .3rem;
            }
        }
    </style>
</head>
<body>
    <section class="h-100 gradient-form" style="background-color: #eee;">
        <div class="container py-5 h-100">
            <div class="row d-flex justify-content-center align-items-center h-100">
                <div class="col-xl-10">
                    <div class="card rounded-3 text-black">
                        <div class="row g-0">
                            <div class="col-lg-6">
                                <div class="card-body p-md-5 mx-md-4">
                                    <div class="text-center">
                                        <img src="https://wellness.zuelligfoundation.ngo/wp-content/uploads/2024/02/logo-300x81.png"
                                            style="width: 185px;" alt="logo">
                                        <h4 class="mt-1 mb-5 pb-1"></h4>
                                    </div>
                                    <form method="POST" action="">
                                        <p>Please login to your account</p>
                                        <div data-mdb-input-init class="form-outline mb-4">
                                            <input type="email" id="form2Example11" class="form-control" name="email"
                                                placeholder="Input admin email" />
                                            <label class="form-label" for="form2Example11">Username</label>
                                        </div>
                                        <div data-mdb-input-init class="form-outline mb-4">
                                            <input type="password" id="form2Example22" class="form-control" name="password" />
                                            <label class="form-label" for="form2Example22">Password</label>
                                        </div>
                                        <div class="text-center pt-1 mb-5 pb-1">
                                            <button data-mdb-button-init data-mdb-ripple-init class="btn btn-primary btn-block fa-lg gradient-custom-2 mb-3" type="submit">Log in</button>
                                            <a class="text-muted" href="#!">Forgot password?</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/3.10.2/mdb.min.js"></script>
</body>
</html>