function openEditModal(item) {
    document.getElementById('edit-modal').style.display = 'block';

    // Populate view mode with item data (example, adjust as needed)
    document.getElementById('view-image').src = item.image;
    document.getElementById('view-name').textContent = item.name;
    document.getElementById('view-price').textContent = item.price;
    document.getElementById('view-description').textContent = item.description;

    // Set form fields
    document.getElementById('edit-id').value = item.id;
    document.getElementById('edit-image-preview').src = item.image;
    document.getElementById('edit-name').value = item.name;
    document.getElementById('edit-price').value = item.price;
    document.getElementById('edit-description').value = item.description;
    document.getElementById('edit-category').value = item.category;

    // Initially show view mode, hide form
    document.getElementById('view-mode').style.display = 'block';
    document.getElementById('edit-item-form').style.display = 'none';
}

function closeModal() {
    document.getElementById('edit-modal').style.display = 'none';
}

function enableEditMode() {
    document.getElementById('view-mode').style.display = 'none';
    document.getElementById('edit-item-form').style.display = 'block';
}

function cancelEditMode() {
    document.getElementById('edit-item-form').style.display = 'none';
    document.getElementById('view-mode').style.display = 'block';
}
