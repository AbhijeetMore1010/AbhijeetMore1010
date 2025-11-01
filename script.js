document.addEventListener('DOMContentLoaded', () => {
    const suggestionsGrid = document.getElementById('suggestions-grid');
    const generateButton = document.getElementById('generate-button');
    const keywordInput = document.getElementById('keyword-input');

    // Function to show a loading spinner
    const showLoadingSpinner = () => {
        suggestionsGrid.innerHTML = '<div class="loader"></div>';
    };

    // Function to render domain suggestion cards
    const renderSuggestionCards = (suggestions) => {
        suggestionsGrid.innerHTML = '';
        suggestions.forEach(suggestion => {
            const card = document.createElement('div');
            card.className = 'domain-card';
            card.innerHTML = `
                <span class="domain-name">${suggestion.domain}</span>
                <span class="availability-status ${suggestion.available ? 'available' : 'unavailable'}"></span>
                <div class="domain-actions">
                    <button class="copy-button">Copy</button>
                    <button class="register-button">Register</button>
                </div>
            `;
            suggestionsGrid.appendChild(card);
        });
    };

    // Example usage:
    // showLoadingSpinner();
    // setTimeout(() => {
    //     const dummySuggestions = [
    //         { domain: 'example.com', available: true },
    //         { domain: 'test.io', available: false },
    //         { domain: 'demo.in', available: true },
    //     ];
    //     renderSuggestionCards(dummySuggestions);
    // }, 2000);

    generateButton.addEventListener('click', () => {
        const keyword = keywordInput.value.trim();
        const tld = document.getElementById('tld-select').value;
        if (keyword) {
            showLoadingSpinner();
            fetch(`api/generate-domain.php?keyword=${keyword}&tld=${tld}`)
                .then(response => response.json())
                .then(data => {
                    renderSuggestionCards(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while generating domain suggestions.');
                });
        } else {
            alert('Please enter a keyword.');
        }
    });

    suggestionsGrid.addEventListener('click', (e) => {
        if (e.target.classList.contains('copy-button')) {
            const domainName = e.target.closest('.domain-card').querySelector('.domain-name').textContent;
            navigator.clipboard.writeText(domainName).then(() => {
                alert(`${domainName} copied to clipboard!`);
            });
        }

        if (e.target.classList.contains('register-button')) {
            const domainName = e.target.closest('.domain-card').querySelector('.domain-name').textContent;
            // In a real application, you would redirect to a domain registrar's website.
            alert(`Redirecting to register ${domainName}...`);
        }
    });
});
