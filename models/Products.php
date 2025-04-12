class Product {
    private $products = [];

    public function __construct() {
        if (file_exists('data/products.json')) {
            $this->products = json_decode(file_get_contents('data/products.json'), true);
        }
    }

    public function getAll() {
        return $this->products;
    }

    public function add($product) {
        $this->products[] = $product;
        file_put_contents('data/products.json', json_encode($this->products));
    }
}
