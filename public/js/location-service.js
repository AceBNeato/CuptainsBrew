/**
 * OpenStreetMap integration for CuptainsBrew
 * - Address autocomplete
 * - Distance calculation
 * - Delivery fee calculation
 */

// Store cafe location (Tagum City coordinates)
const CAFE_LOCATION = {
    lat: 7.4478, // Tagum City - CRW5+VP9 Purok Malinis, 5th Avenue
    lon: 125.8078,
    address: "Captain's Brew Cafe, Tagum City, Davao del Norte"
};

// Delivery fee configuration
const BASE_DELIVERY_FEE = 30.00;   // Base delivery fee in PHP
const FEE_PERCENTAGE = 0.10;       // 10% per 10km
const DISTANCE_THRESHOLD = 10.00;  // Distance threshold in km
const MIN_DELIVERY_FEE = 30.00;    // Minimum delivery fee
const MAX_DELIVERY_FEE = 150.00;   // Maximum delivery fee

// Customer location
let customerLocation = {
    lat: null,
    lon: null,
    address: ""
};

// Preview map instance
let previewMap = null;
let previewMarker = null;

/**
 * Initialize address autocomplete with map preview
 * @param {string} inputId - The ID of the input field
 * @param {string} suggestionsId - The ID of the suggestions container
 * @param {string} mapId - The ID of the map container
 */
function initAddressAutocomplete(inputId, suggestionsId, mapId) {
    const inputElement = document.getElementById(inputId);
    const suggestionsElement = document.getElementById(suggestionsId);
    const mapElement = document.getElementById(mapId);
    
    // Initialize map with Tagum City cafe location
    let map = L.map(mapElement).setView([CAFE_LOCATION.lat, CAFE_LOCATION.lon], 14);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    // Add marker for cafe location
    const cafeMarker = L.marker([CAFE_LOCATION.lat, CAFE_LOCATION.lon], {
        icon: L.divIcon({
            className: 'cafe-marker',
            html: '<div class="marker-icon" style="background-color:#2C6E8A;width:12px;height:12px;border-radius:50%;border:2px solid white;"></div>',
            iconSize: [16, 16],
            iconAnchor: [8, 8]
        })
    }).addTo(map);
    cafeMarker.bindPopup("<b>Captain's Brew Cafe</b><br>Tagum City, Davao del Norte").openPopup();
    
    // Add event listeners
    inputElement.addEventListener('input', debounce(function() {
        const query = inputElement.value.trim();
        if (query.length < 3) {
            suggestionsElement.innerHTML = '';
            suggestionsElement.style.display = 'none';
            return;
        }
        
        searchAddress(query, suggestionsElement);
    }, 500));
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target !== inputElement && e.target !== suggestionsElement) {
            suggestionsElement.style.display = 'none';
        }
    });
    
    // Use current location if available
    const useCurrentLocationBtn = document.getElementById('use_current_location');
    if (useCurrentLocationBtn) {
        useCurrentLocationBtn.addEventListener('click', function() {
            if (navigator.geolocation) {
                useCurrentLocationBtn.disabled = true;
                useCurrentLocationBtn.textContent = 'Getting location...';
                
                // Add options for better geolocation performance
                const options = {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                };
                
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lon = position.coords.longitude;
                        
                        // Reverse geocode to get address
                        reverseGeocode(lat, lon, function(address) {
                            if (address) {
                                inputElement.value = address;
                                document.getElementById('lat').value = lat;
                                document.getElementById('lon').value = lon;
                                
                                customerLocation.lat = lat;
                                customerLocation.lon = lon;
                                customerLocation.address = address;
                                
                                calculateDeliveryFee();
                                updateMapPreview(lat, lon, address);
                            } else {
                                // Handle case where reverse geocoding fails
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Address Error',
                                    text: 'Unable to get your address. Please enter it manually.'
                                });
                            }
                            
                            useCurrentLocationBtn.disabled = false;
                            useCurrentLocationBtn.textContent = 'Use my location';
                        });
                    },
                    function(error) {
                        console.error('Geolocation error:', error);
                        let errorMessage = 'Unable to get your current location. Please enter your address manually.';
                        
                        // Provide more specific error messages
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage = 'Location permission was denied. Please enable location access in your browser settings.';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage = 'Location information is unavailable. Please try again or enter your address manually.';
                                break;
                            case error.TIMEOUT:
                                errorMessage = 'Location request timed out. Please try again or enter your address manually.';
                                break;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Location Error',
                            text: errorMessage
                        });
                        
                        useCurrentLocationBtn.disabled = false;
                        useCurrentLocationBtn.textContent = 'Use my location';
                    },
                    options
                );
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Location Not Supported',
                    text: 'Your browser does not support geolocation. Please enter your address manually.'
                });
            }
        });
    }
}

/**
 * Search for addresses using Nominatim API
 * @param {string} query - The search query
 * @param {HTMLElement} resultsContainer - The container for results
 */
function searchAddress(query, resultsContainer) {
    // Clear previous results
    resultsContainer.innerHTML = '';
    
    // Show loading indicator
    resultsContainer.innerHTML = '<div class="p-2 text-gray-500">Searching...</div>';
    resultsContainer.style.display = 'block';
    
    // Add preference for Philippines addresses with focus on Tagum City area
    const viewbox = "125.7,7.3,125.9,7.5"; // Bounding box for Tagum City region
    
    // Build the Nominatim API URL with more detailed parameters
    const apiUrl = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&countrycodes=ph&viewbox=${viewbox}&bounded=1&addressdetails=1&dedupe=1`;
    
    // Fetch results from Nominatim
    fetch(apiUrl)
        .then(response => response.json())
        .then(data => {
            resultsContainer.innerHTML = '';
            
            if (data.length === 0) {
                resultsContainer.innerHTML = '<div class="p-2 text-gray-500">No results found</div>';
                return;
            }
            
            // Create result items
            data.forEach(item => {
                const resultItem = document.createElement('div');
                resultItem.className = 'p-2 hover:bg-gray-100 cursor-pointer';
                
                // Format address in a more readable way if addressdetails are available
                const formattedAddress = formatAddress(item);
                resultItem.textContent = formattedAddress;
                
                // Add click event to select address
                resultItem.addEventListener('click', function() {
                    document.getElementById('delivery_address').value = formattedAddress;
                    document.getElementById('lat').value = item.lat;
                    document.getElementById('lon').value = item.lon;
                    
                    // Store customer location
                    customerLocation.lat = parseFloat(item.lat);
                    customerLocation.lon = parseFloat(item.lon);
                    customerLocation.address = formattedAddress;
                    
                    // Calculate delivery fee
                    calculateDeliveryFee();
                    
                    // Update map preview
                    updateMapPreview(customerLocation.lat, customerLocation.lon, customerLocation.address);
                    
                    // Hide suggestions
                    resultsContainer.style.display = 'none';
                });
                
                resultsContainer.appendChild(resultItem);
            });
            
            resultsContainer.style.display = 'block';
        })
        .catch(error => {
            console.error('Error fetching address suggestions:', error);
            resultsContainer.innerHTML = '<div class="p-2 text-gray-500">Error fetching results</div>';
        });
}

/**
 * Format address in a more readable way
 * @param {Object} item - The address item from Nominatim
 * @returns {string} - Formatted address
 */
function formatAddress(item) {
    if (item.address) {
        const parts = [];
        
        // Add building or house number if available
        if (item.address.house_number) {
            parts.push(item.address.house_number);
        }
        
        // Add road/street
        if (item.address.road) {
            parts.push(item.address.road);
        }
        
        // Add suburb/neighborhood
        if (item.address.suburb) {
            parts.push(item.address.suburb);
        }
        
        // Add city/town
        if (item.address.city || item.address.town || item.address.village) {
            parts.push(item.address.city || item.address.town || item.address.village);
        }
        
        // Add state/province
        if (item.address.state || item.address.province) {
            parts.push(item.address.state || item.address.province);
        }
        
        // Add postal code if available
        if (item.address.postcode) {
            parts.push(item.address.postcode);
        }
        
        if (parts.length > 0) {
            return parts.join(', ');
        }
    }
    
    // Fallback to display_name if address details are not available
    return item.display_name;
}

/**
 * Reverse geocode coordinates to get address
 * @param {number} lat - Latitude
 * @param {number} lon - Longitude
 * @param {Function} callback - Callback function
 */
function reverseGeocode(lat, lon, callback) {
    const apiUrl = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=18&addressdetails=1`;
    
    fetch(apiUrl)
        .then(response => response.json())
        .then(data => {
            if (data && data.display_name) {
                const formattedAddress = formatAddress(data);
                callback(formattedAddress);
            } else {
                callback(null);
            }
        })
        .catch(error => {
            console.error('Error reverse geocoding:', error);
            callback(null);
        });
}

/**
 * Update map preview with selected location
 * @param {number} lat - Latitude
 * @param {number} lon - Longitude
 * @param {string} address - Address text
 */
function updateMapPreview(lat, lon, address) {
    if (!previewMap) return;
    
    // Remove existing customer marker if any
    if (previewMarker) {
        previewMap.removeLayer(previewMarker);
    }
    
    // Add new marker
    previewMarker = L.marker([lat, lon]).addTo(previewMap)
        .bindPopup('Delivery Location: ' + address)
        .openPopup();
    
    // Draw route line between cafe and delivery location
    const routePoints = [
        [CAFE_LOCATION.lat, CAFE_LOCATION.lon],
        [lat, lon]
    ];
    
    // Remove existing polylines
    previewMap.eachLayer(function(layer) {
        if (layer instanceof L.Polyline && !(layer instanceof L.Marker)) {
            previewMap.removeLayer(layer);
        }
    });
    
    // Add new polyline
    L.polyline(routePoints, {color: '#2C6E8A', weight: 5}).addTo(previewMap);
    
    // Fit map to show both points
    previewMap.fitBounds(routePoints, {padding: [30, 30]});
}

/**
 * Calculate the distance between two points using Haversine formula
 * @param {number} lat1 - Latitude of point 1
 * @param {number} lon1 - Longitude of point 1
 * @param {number} lat2 - Latitude of point 2
 * @param {number} lon2 - Longitude of point 2
 * @returns {number} - Distance in kilometers
 */
function calculateDistance(lat1, lon1, lat2, lon2) {
    // Radius of the Earth in kilometers
    const R = 6371;
    
    // Convert latitude and longitude from degrees to radians
    const dLat = toRad(lat2 - lat1);
    const dLon = toRad(lon2 - lon1);
    
    // Haversine formula
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * 
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    const distance = R * c;
    
    return distance;
}

/**
 * Convert degrees to radians
 * @param {number} degrees - Value in degrees
 * @returns {number} - Value in radians
 */
function toRad(degrees) {
    return degrees * (Math.PI / 180);
}

/**
 * Calculate delivery fee based on distance
 */
function calculateDeliveryFee() {
    if (!customerLocation.lat || !customerLocation.lon) {
        console.error('Customer location not set');
        return;
    }
    
    // Calculate distance
    const distance = calculateDistance(
        CAFE_LOCATION.lat, CAFE_LOCATION.lon,
        customerLocation.lat, customerLocation.lon
    );
    
    // Calculate fee based on distance
    // Base fee + additional percentage for each distance threshold
    let deliveryFee = BASE_DELIVERY_FEE;
    
    // Add 10% of 100 pesos (10 pesos) for each 10km
    if (distance > DISTANCE_THRESHOLD) {
        const additionalDistanceUnits = Math.floor(distance / DISTANCE_THRESHOLD);
        const additionalFee = additionalDistanceUnits * (FEE_PERCENTAGE * 100);
        deliveryFee += additionalFee;
    }
    
    // Apply min/max constraints
    deliveryFee = Math.max(MIN_DELIVERY_FEE, Math.min(MAX_DELIVERY_FEE, deliveryFee));
    
    // Round to nearest peso
    deliveryFee = Math.round(deliveryFee);
    
    // Update UI elements
    const deliveryFeeElement = document.getElementById('delivery_fee');
    const deliveryFeeDisplay = document.getElementById('delivery_fee_display');
    const distanceDisplay = document.getElementById('distance_display');
    const totalDisplay = document.getElementById('total_display');
    const subtotalElement = document.getElementById('subtotal');
    
    if (deliveryFeeElement) {
        deliveryFeeElement.value = deliveryFee;
    }
    
    if (deliveryFeeDisplay) {
        deliveryFeeDisplay.textContent = '₱' + deliveryFee.toFixed(2);
    }
    
    if (distanceDisplay) {
        distanceDisplay.textContent = distance.toFixed(2) + ' km';
    }
    
    // Update total if subtotal is available
    if (totalDisplay && subtotalElement) {
        const subtotal = parseFloat(subtotalElement.value);
        const total = subtotal + deliveryFee;
        totalDisplay.textContent = '₱' + total.toFixed(2);
    }
}

/**
 * Show order summary in a modal for confirmation
 * @param {Object} orderData - Order data to display
 * @returns {Promise} - Promise that resolves when user confirms or cancels
 */
function showOrderConfirmation(orderData) {
    return new Promise((resolve, reject) => {
        // Create items HTML
        let itemsHtml = '';
        orderData.items.forEach(item => {
            itemsHtml += `
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <div>
                        <div class="font-medium">${item.name}</div>
                        ${item.variation ? `<div class="text-sm text-gray-500">Variation: ${item.variation}</div>` : ''}
                        <div class="text-sm">Qty: ${item.quantity}</div>
                    </div>
                    <div class="text-right">
                        <div>₱${item.price.toFixed(2)}</div>
                        <div class="font-medium">₱${item.subtotal.toFixed(2)}</div>
                    </div>
                </div>
            `;
        });
        
        Swal.fire({
            title: 'Confirm Your Order',
            html: `
                <div class="text-left">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold mb-2">Delivery Details</h3>
                        <div class="text-sm mb-1"><span class="font-medium">Address:</span> ${orderData.address}</div>
                        <div class="text-sm mb-1"><span class="font-medium">Payment Method:</span> ${orderData.paymentMethodText || orderData.paymentMethod}</div>
                        <div class="text-sm"><span class="font-medium">Distance:</span> ${orderData.distance} km</div>
                    </div>
                    
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold mb-2">Order Items</h3>
                        <div class="max-h-40 overflow-y-auto">
                            ${itemsHtml}
                        </div>
                    </div>
                    
                    <div class="border-t border-gray-200 pt-2">
                        <div class="flex justify-between mb-1">
                            <span>Subtotal:</span>
                            <span>₱${orderData.subtotal.toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between mb-1">
                            <span>Delivery Fee:</span>
                            <span>₱${orderData.deliveryFee.toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between font-semibold text-lg">
                            <span>Total:</span>
                            <span>₱${orderData.total.toFixed(2)}</span>
                        </div>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Place Order',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#2C6E8A',
            cancelButtonColor: '#6B7280',
            customClass: {
                popup: 'swal-wide',
            }
        }).then((result) => {
            if (result.isConfirmed) {
                resolve(true);
            } else {
                resolve(false);
            }
        }).catch(error => {
            reject(error);
        });
    });
}

/**
 * Debounce function to limit API calls
 * @param {Function} func - Function to debounce
 * @param {number} delay - Delay in milliseconds
 * @returns {Function} - Debounced function
 */
function debounce(func, delay) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), delay);
    };
}

/**
 * Initialize the map for delivery tracking (for rider dashboard)
 * @param {string} mapId - The ID of the map container
 * @param {Object} orderLocation - The order delivery location
 */
function initDeliveryMap(mapId, orderLocation) {
    if (!mapId || !orderLocation || !orderLocation.lat || !orderLocation.lon) {
        console.error('Invalid map parameters');
        return;
    }
    
    // Initialize map
    const map = L.map(mapId).setView([orderLocation.lat, orderLocation.lon], 15);
    
    // Add OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Add marker for delivery location
    L.marker([orderLocation.lat, orderLocation.lon])
        .addTo(map)
        .bindPopup('Delivery Location')
        .openPopup();
    
    // Add marker for cafe location
    L.marker([CAFE_LOCATION.lat, CAFE_LOCATION.lon])
        .addTo(map)
        .bindPopup('Cafe Location: ' + CAFE_LOCATION.address);
    
    // Draw route line between cafe and delivery location
    const routePoints = [
        [CAFE_LOCATION.lat, CAFE_LOCATION.lon],
        [orderLocation.lat, orderLocation.lon]
    ];
    
    L.polyline(routePoints, {color: '#2C6E8A', weight: 5}).addTo(map);
    
    // Fit map to show both points
    map.fitBounds(routePoints, {padding: [30, 30]});
} 