document.addEventListener('DOMContentLoaded', function () {
    const accordionTitles = document.querySelectorAll('.iw-dyc-faq-accordion-title');
    const accordions = document.querySelectorAll('.iw-dyc-faq-accordion-text');

    accordionTitles.forEach(function (title) {
        title.addEventListener('click', function () {
            const content = this.nextElementSibling;

            // Toggle current clicked accordion
            if (content.classList.contains('active')) {
                content.style.display = 'none';
                content.classList.remove('active');
            } else {
                content.style.display = 'block';
                content.classList.add('active');
            }

            // Close all other accordions
            accordions.forEach(function (otherContent) {
                if (otherContent !== content) {
                    otherContent.style.display = 'none';
                    otherContent.classList.remove('active');
                }
            });
        });
    });

    // Open the first accordion on page load
    if (accordions.length > 0) {
        accordions[0].style.display = 'block';
        accordions[0].classList.add('active');
    }
});
