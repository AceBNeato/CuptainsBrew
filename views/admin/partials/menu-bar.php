<div class="menu-bar">
    <div class="menu-item <?= ($currentCategory === 'coffee') ? 'active' : '' ?>" 
         onclick="loadCategory('coffee')">Coffee</div>
         
    <div class="menu-item <?= ($currentCategory === 'non-coffee') ? 'active' : '' ?>" 
         onclick="loadCategory('non-coffee')">Non-Coffee</div>
         
    <div class="menu-item <?= ($currentCategory === 'frappe') ? 'active' : '' ?>" 
         onclick="loadCategory('frappe')">Frappe</div>
         
    <div class="menu-item <?= ($currentCategory === 'milktea') ? 'active' : '' ?>" 
         onclick="loadCategory('milktea')">MilkTea</div>
         
    <div class="menu-item <?= ($currentCategory === 'soda') ? 'active' : '' ?>" 
         onclick="loadCategory('soda')">Soda</div>

    <div class="search-box">
        <input type="text" class="search-input" placeholder="🔍 Search item" id="search-input" />
    </div>
</div>

<script>
function loadCategory(category) {
    const url = new URL(window.location.href);
    url.searchParams.set('category', category);
    window.location.href = url.toString();
}
</script>