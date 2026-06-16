function submitFeedback() {
    const name = document.getElementById("name").value.trim();
    const message = document.getElementById("message").value.trim();

    const rating = document.querySelector(
        'input[name="rating"]:checked'
    );

    // Validation
    if (name === "" || message === "") {
        alert("Please fill in all fields!");
        return;
    }

    if (!rating) {
        alert("Please select a rating!");
        return;
    }

    const stars = rating.value;

    alert(
        `Thank you ${name}!\n\nRating: ${stars} Stars\nMessage: ${message}`
    );

    // Reset form
    document.getElementById("feedbackForm").reset();
}