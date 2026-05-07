/**
 * Shared Google Maps Configuration for Britz Blythe
 */

const STORE_LOCATION = { lat: 14.5995, lng: 120.9842 }; // Central Warehouse (Manila)

/**
 * Centralized Map Initializer
 * @param {HTMLElement} mapDiv - The container for the map
 * @param {Object} coords - {lat, lng} (Optional: defaults to STORE_LOCATION)
 * @param {HTMLInputElement} inputElement - The search input for Autocomplete
 * @returns {Object} - References to map, marker, and autocomplete
 */
async function initBritzBlytheMap(mapDiv, coords = null, inputElement = null) {
    if (typeof google === 'undefined' || !google.maps.marker) return null;

    const geocoder = new google.maps.Geocoder();
    const position = (coords && coords.lat && coords.lng)
        ? { lat: parseFloat(coords.lat), lng: parseFloat(coords.lng) }
        : STORE_LOCATION;

    const map = new google.maps.Map(mapDiv, {
        zoom: 15,
        center: position,
        mapId: "BRITZ_BLYTHE_MAP_ID", // Ensure this is created in your Cloud Console
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false
    });

    const marker = new google.maps.marker.AdvancedMarkerElement({
        map: map,
        position: position,
        gmpDraggable: true,
        title: "Your Delivery Location"
    });

    // Handle Pin Dragging (Reverse Geocoding)
    marker.addListener('dragend', () => {
        const pos = marker.position; // AdvancedMarkerElement uses .position
        map.panTo(pos);
        geocoder.geocode({ location: pos }, (results, status) => {
            if (status === "OK" && results[0]) {
                updateFieldsFromComponents(results[0].address_components, results[0].formatted_address);
                updateShippingUI(pos);
            }
        });
    });

    // Handle Search Input (Autocomplete)
    if (inputElement) {
        const autocomplete = new google.maps.places.Autocomplete(inputElement, {
            types: ['address'],
            componentRestrictions: { country: 'ph' },
            fields: ['address_components', 'formatted_address', 'geometry']
        });

        autocomplete.addListener('place_changed', () => {
            const place = autocomplete.getPlace();
            if (!place.geometry) return;

            if (place.geometry.viewport) map.fitBounds(place.geometry.viewport);
            else {
                map.setCenter(place.geometry.location);
                map.setZoom(17);
            }
            marker.position = place.geometry.location;
            updateFieldsFromComponents(place.address_components, place.formatted_address);
            updateShippingUI(place.geometry.location);
        });

        return { map, marker, autocomplete };
    }

    return { map, marker };
}

/**
 * Calculates and updates shipping estimate based on distance
 */
function updateShippingUI(coords) {
    const display = document.getElementById('shipping-estimate-box');
    if (!display || typeof google === 'undefined' || !google.maps.geometry) return;

    const lat = typeof coords.lat === 'function' ? coords.lat() : coords.lat;
    const lng = typeof coords.lng === 'function' ? coords.lng() : coords.lng;

    const store = new google.maps.LatLng(STORE_LOCATION.lat, STORE_LOCATION.lng);
    const target = new google.maps.LatLng(lat, lng);

    const distanceKm = google.maps.geometry.spherical.computeDistanceBetween(store, target) / 1000;

    // Pricing logic: ₱50 base + ₱5 per KM
    const baseFee = 50;
    const kmRate = 5;
    const total = baseFee + (distanceKm * kmRate);

    display.innerHTML = `
        <div style="font-size: 0.85rem; font-weight: 600; color: var(--accent);">Shipping Estimate</div>
        <div style="font-size: 0.75rem; color: var(--text-muted);">${distanceKm.toFixed(2)} km from warehouse — <strong>₱${total.toLocaleString(undefined, { minimumFractionDigits: 2 })}</strong></div>
    `;

    // Save value to hidden input for checkout submission
    const costInput = document.getElementById('shipping_cost_input');
    if (costInput) {
        costInput.value = total.toFixed(2);
    }

    display.style.display = 'block';
}

/**
 * Updates form fields based on Google Address Components
 * @param {Array} components - Google Maps address_components array
 * @param {string|null} formattedAddress - The full formatted address string
 */
function updateFieldsFromComponents(components, formattedAddress = null) {
    let city = '', state = '', zip = '';
    for (const component of components) {
        const type = component.types[0];
        if (type === 'locality' || type === 'administrative_area_level_2') city = component.long_name;
        if (type === 'administrative_area_level_1') state = component.short_name;
        if (type === 'postal_code') zip = component.long_name;
    }

    if (formattedAddress && document.getElementById('address')) document.getElementById('address').value = formattedAddress;
    if (document.getElementById('city')) document.getElementById('city').value = city;
    if (document.getElementById('state')) document.getElementById('state').value = state;
    if (document.getElementById('zip_code')) document.getElementById('zip_code').value = zip;
}