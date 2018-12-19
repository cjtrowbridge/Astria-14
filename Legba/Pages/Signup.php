<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="/favicon.ico">

    <title>Log In</title>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" integrity="sha384-WskhaSGFgHYWDcbwN70/dfYBj47jz9qbsMId/iRN3ewGhXQFZCSftd1LZCfmhktB" crossorigin="anonymous">

  </head>

  <body>
    
    <div class="container">
      <div class="row">
        <div class="col-12">
          <form class="form" action="/signup" method="post">
            <h1 class="h3 mb-3">Please Sign Up</h1>
            <input type="email" id="inputEmail" class="mb-3 form-control" placeholder="Email address" required autofocus>
            <input type="password" id="inputPassword" class="mb-3 form-control" placeholder="Password" required>
            <input type="password" id="inputPasswordConfirm" class="mb-3 form-control" placeholder="Confirm Password" required>
            <input type="submit" value="Sign Up" class="mb-3 form-control btn btn-block btn-primary">
            <a href="/signup" class="form-control btn btn-block btn-success">Sign Up</a>

            <!-- Insert OAuth Options Here -->

          </form>
        </div>
      </div>
    </div>
  </body>
</html>
