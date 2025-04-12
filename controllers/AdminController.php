class AdminController extends Controller {
    public function menu() {
        $productModel = $this->model('Product');
        $products = $productModel->getAll();
        $this->view('admin/menu', ['products' => $products]);
    }

    public function addProduct() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productModel = $this->model('Product');
            $productModel->add([
                'name' => $_POST['name'],
                'price' => $_POST['price'],
                'description' => $_POST['description']
            ]);
            header('Location: /admin/menu');
        }
    }
}
