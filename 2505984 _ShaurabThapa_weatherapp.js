document.addEventListener('DOMContentLoaded', () => {
    // Selecting DOM elements
    const searchBox = document.querySelector('.search-box input');
    const searchButton = document.querySelector('.search-box button');
    const cityElement = document.querySelector('.city');
    const dayElement = document.querySelector('.day');
    const dateElement = document.querySelector('.date');
    const temperatureElement = document.querySelector('.temperature');
    const descriptionElement = document.querySelector('.description');
    const humidityElement = document.querySelector('.info-humidity span');
    const windElement = document.querySelector('.info-wind span');
    const windDirection = document.querySelector('.info-wind-deg span');
    const pressureElement = document.querySelector('.info-pressure span');
    const weatherIcon = document.querySelector('.info-weather img');
    const error404 = document.querySelector('.not-found');
    const container = document.querySelector('.container');
    const weatherBox = document.querySelector('.weather-box');
    const weatherDetail = document.querySelector('.weather-detail');
    const cityBox = document.querySelector('.city-box');
    const dateBox = document.querySelector('.date-box');
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    async function getAndDisplayWeather(city) {
        let data;
        if (navigator.onLine) {
            try {
                const response = await fetch(`http://localhost/Prototype%202/connection.php?q=${city}`);
                data = await response.json();
                if (data.error || data.cod === '404') {
                    showError();
                    return;
                }
                localStorage.setItem(city, JSON.stringify(data)); // Save data to localStorage
            } catch (error) {
                console.error('Error fetching weather data:', error);
                showError();
                return;
            }
        } else {
            data = JSON.parse(localStorage.getItem(city)); // Retrieve from localStorage
            if (!data) {
                showError();
                return;
            }
        }
        updateWeather(data);
    }

    function updateWeather(data) {
        const { city, date, temperature, description, humidity, wind_speed, wind_direction, pressure, icon } = data[0];
        const dateObj = new Date(date);
        cityElement.textContent = city;
        dayElement.textContent = days[dateObj.getDay()];
        dateElement.textContent = `${dateObj.getFullYear()}-${dateObj.getMonth() + 1}-${dateObj.getDate()}`;
        temperatureElement.innerHTML = `${Math.round(temperature)}<span>°C</span>`;
        descriptionElement.textContent = description;
        humidityElement.textContent = `${humidity}%`;
        windElement.textContent = `${wind_speed}m/s`;
        windDirection.textContent = `${wind_direction}°`;
        pressureElement.textContent = `${pressure}hPa`;
        weatherIcon.src = getWeatherIcon(icon);

        container.style.height = '590px';
        weatherBox.classList.add('active');
        weatherDetail.classList.add('active');
        cityBox.classList.add('active');
        dateBox.classList.add('active');
        error404.classList.remove('active');
    }

    function showError() {
        container.style.height = '400px';
        weatherBox.classList.remove('active');
        weatherDetail.classList.remove('active');
        cityBox.classList.remove('active');
        dateBox.classList.remove('active');
        error404.classList.add('active');
    }

    searchButton.addEventListener('click', () => {
        const city = searchBox.value.trim();
        if (city) {
            getAndDisplayWeather(city);
        }
    });

    getAndDisplayWeather('Ilam');
});

function getWeatherIcon(icon) {
    const iconCode = icon.replace('n', 'd');
    return `https://openweathermap.org/img/wn/${iconCode}@2x.png`;
}
