<?php
/**
 * Google OAuth Verification
 **/
?>

<div class="wrap wbps-g-oauth">
    <h1 class="wp-heading-inline"><?php _e('Google Verification', 'wbps');?></h1>
    <!-- Place this where you want the sign-in button to render -->
    <div class="g-signin2" data-onsuccess="onSignIn"></div>
    
</div>

<script>
  function onSignIn(googleUser) {
    // Get the user's ID token and basic profile information
    var id_token = googleUser.getAuthResponse().id_token;
    var profile = googleUser.getBasicProfile();
    
    console.log(`ID TOKEN ${id_token}`);
    
    // Send the ID token to your server for verification
    // ...
  }
</script>

<!-- Load the Google Sign-In API -->
<!--<script src="https://apis.google.com/js/platform.js" async defer></script>-->
<script>
  gapi.load('auth2', function() {
    gapi.auth2.init({
      client_id: '267586530797-h1cusreurompr5ho1v9h4q760sppddbg.apps.googleusercontent.com',
    });
  });
</script>