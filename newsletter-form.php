<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Newsletter</title>

<style>
body{
    font-family:Arial,sans-serif;
    max-width:500px;
    margin:50px auto;
}

input{
    width:100%;
    padding:12px;
    margin-bottom:10px;
}

button{
    padding:12px 20px;
    background:#0077ff;
    color:white;
    border:none;
    cursor:pointer;
}

#message{
    margin-top:15px;
}
</style>

</head>
<body>

<h2>Subscribe to Newsletter</h2>

<form id="newsletterForm">

    <input
        type="email"
        name="email"
        placeholder="Email Address"
        required
    >

    <input
        type="text"
        name="name"
        placeholder="Your Name"
    >

    <label>
        <input
            type="checkbox"
            name="consent"
            required
        >
        I agree to receive newsletters.
    </label>

    <br><br>

    <button type="submit">
        Subscribe
    </button>

</form>

<div id="message"></div>

<script>

document
.getElementById('newsletterForm')
.addEventListener('submit', async function(e){

    e.preventDefault();

    const formData = new FormData(this);

    const response = await fetch(
        'api/subscribe.php',
        {
            method:'POST',
            body:formData
        }
    );

    const data = await response.json();

    document.getElementById('message').innerHTML =
        data.message;
});

</script>

</body>
</html>