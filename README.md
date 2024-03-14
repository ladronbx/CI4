### Pasos para crear un formulario para insertar noticias.

1. **Habilitar filtro CSRF**. En el archivo **`app/Config/Filters.php`** y actualizo la `$methods`propiedad de la siguiente manera:
    
    ```php
        public $methods = [
            'post' => ['csrf'],
        ];
    
    ```
    
    Configura el filtro CSRF para que se habilite para todas las solicitudes **POST** .
    
    <aside>
    👉🏽 En general, si utiliza `$methods`filtros, debe [desactivar el enrutamiento automático (heredado)](https://codeigniter.com/user_guide/incoming/routing.html#use-defined-routes-only) porque [el enrutamiento automático (heredado)](https://codeigniter.com/user_guide/incoming/routing.html#auto-routing-legacy) permite que cualquier método HTTP acceda a un controlador. Acceder al controlador con un método inesperado podría pasar por alto el filtro.
    
    </aside>
    
    - ¿Qué es CSRF?
        
        CSRF (Cross-Site Request Forgery) es un tipo de ataque en el que un atacante aprovecha la sesión de un usuario autenticado para realizar acciones no deseadas en un sitio web en el que el usuario está autenticado. Esto se logra mediante la ejecución de acciones no autorizadas en nombre del usuario autenticado, aprovechando que el usuario ya ha iniciado sesión en el sitio.
        
2. Agregar enrutamiento en **Routes.php**:
    
    ```php
    <?php
    
    // ...
    
    use App\Controllers\News;
    use App\Controllers\Pages;
    
    $routes->get('news', [News::class, 'index']);
    $routes->get('news/new', [News::class, 'new']); // Add this line
    $routes->post('news', [News::class, 'create']); // Add this line
    ```
    
    - **El método `create()` lo definiré luego en el modelo!!!**
3. Creo la **vista** con en **`app/Views/news/create.php` del formulario**
    
    ```php
    <h2><?= esc($title) ?></h2>
    
    <?= session()->getFlashdata('error') ?>
    <?= validation_list_errors() ?>
    
    <form action="/news" method="post">
        <?= csrf_field() ?>
    
        <label for="title">Title</label>
        <input type="input" name="title" value="<?= set_value('title') ?>">
        <br>
    
        <label for="body">Text</label>
        <textarea name="body" cols="45" rows="4"><?= set_value('body') ?></textarea>
        <br>
    
        <input type="submit" name="submit" value="Create news item">
    </form>
    ```
    
    La **`[session()](https://codeigniter.com/user_guide/general/common_functions.html#session)`**función se utiliza para obtener el objeto Sesión y `session()->getFlashdata('error')`se utiliza para mostrar al usuario el error relacionado con la protección CSRF. Sin embargo, de forma predeterminada, si falla una verificación de validación CSRF, se generará una excepción, por lo que aún no funciona. Consulte [Redirección en caso de error](https://codeigniter.com/user_guide/libraries/security.html#csrf-redirection-on-failure) para obtener más información.
    
    La **`[validation_list_errors()](https://codeigniter.com/user_guide/helpers/form_helper.html#validation_list_errors)`**función proporcionada por [Form Helper](https://codeigniter.com/user_guide/helpers/form_helper.html) se utiliza para informar errores relacionados con la validación del formulario.
    
    La **`[csrf_field()](https://codeigniter.com/user_guide/general/common_functions.html#csrf_field)`**función crea una entrada oculta con un token CSRF que ayuda a proteger contra algunos ataques comunes.
    
    La **`[set_value()](https://codeigniter.com/user_guide/helpers/form_helper.html#set_value)`**función proporcionada por [Form Helper](https://codeigniter.com/user_guide/helpers/form_helper.html) se utiliza para mostrar datos de entrada antiguos cuando se producen errores.
    
4. Ahora en el **controlador News** hay que crear el método `new()` para mostrar el formulario HTML que he creado. 
    
    ```php
    <?php
    
    namespace App\Controllers;
    
    use App\Models\NewsModel;
    use CodeIgniter\Exceptions\PageNotFoundException;
    
    class News extends BaseController
    {
        // ...
    
    public function new()
    {
        helper('form');

        // Muestra cualquier mensaje de error relacionado con CSRF que se haya establecido previamente
        // utilizando session()->setFlashdata('error', 'Mensaje de error CSRF');
        $error = session()->getFlashdata('error');

        return view('templates/header', ['title' => 'Create a news item', 'error' => $error])
            . view('news/create')
            . view('templates/footer');
    }

    }
    ```
    
    Cargamos el [ayudante de formulario](https://codeigniter.com/user_guide/helpers/form_helper.html) con la **`[helper()](https://codeigniter.com/user_guide/general/common_functions.html#helper)`**función. La mayoría de las funciones auxiliares requieren que el asistente se cargue antes de su uso.
    
    Luego devuelve la vista del formulario creado.
    
5. De nuevo en el controlador agrego el método `create()`
    
    ```php
    <?php
    
    namespace App\Controllers;
    
    use App\Models\NewsModel;
    use CodeIgniter\Exceptions\PageNotFoundException;
    
    class News extends BaseController
    {
        // ...
    
            public function create()
        {
            helper('form');
        
            $data = $this->request->getPost(['title', 'body']);
        
            // Aplica las reglas de validación a los datos.
            if (! $this->validate([
                'title' => 'required|max_length[255]|min_length[3]',
                'body'  => 'required|max_length[5000]|min_length[10]',
            ])) {
                // La validación falla, entonces se devuelve el formulario.
                return $this->new();
            }
        
            // Obtiene los datos validados.
            $post = $this->request->getPost();
        
            $model = model(NewsModel::class);
        
            $model->save([
                'title' => $post['title'],
                'slug'  => url_title($post['title'], '-', true),
                'body'  => $post['body'],
            ]);
        
            return view('templates/header', ['title' => 'Create a news item'])
                . view('news/success')
                . view('templates/footer');
        }
    }
    ```
    
    1. comprueba si los datos enviados pasaron las reglas de validación.
    2. guarda la noticia en la base de datos.
    3. devuelve una página de éxito.
    - Explicación del código:
        
        El código anterior agrega mucha funcionalidad.
        
        ### **Recuperar los datos[](https://codeigniter.com/user_guide/tutorial/create_news_items.html#retrieve-the-data)**
        
        Primero, usamos el objeto [IncomingRequest](https://codeigniter.com/user_guide/incoming/incomingrequest.html)`$this->request` , que el marco configura en el controlador.
        
        Obtenemos los elementos necesarios de los datos **POST** del usuario y los configuramos en la `$data`variable.
        
        ### **Validar los datos[](https://codeigniter.com/user_guide/tutorial/create_news_items.html#validate-the-data)**
        
        A continuación, utilizará la función auxiliar proporcionada por el controlador [validarData()](https://codeigniter.com/user_guide/incoming/controllers.html#controller-validatedata) para validar los datos enviados. En este caso, los campos título y cuerpo son obligatorios y en la longitud específica.
        
        CodeIgniter tiene una poderosa biblioteca de validación como se demostró anteriormente. Puede leer más sobre la [biblioteca de Validación](https://codeigniter.com/user_guide/libraries/validation.html) .
        
        Si la validación falla, llamamos al `new()`método que acaba de crear y devolvemos el formulario HTML.
        
        ### **Guardar la noticia[](https://codeigniter.com/user_guide/tutorial/create_news_items.html#save-the-news-item)**
        
        Si la validación pasó todas las reglas, obtenemos los datos validados mediante [$this->validator->getValidated()](https://codeigniter.com/user_guide/libraries/validation.html#validation-getting-validated-data) y los configuramos en la `$post`variable.
        
        El `NewsModel`se carga y se llama. Esto se encarga de pasar la noticia al modelo. El método [save()](https://codeigniter.com/user_guide/models/model.html#model-save) maneja la inserción o actualización del registro automáticamente, en función de si encuentra una clave de matriz que coincida con la clave principal.
        
        Este contiene una nueva función **`[url_title()](https://codeigniter.com/user_guide/helpers/url_helper.html#url_title)`**. Esta función, proporcionada por el [asistente de URL](https://codeigniter.com/user_guide/helpers/url_helper.html) , elimina la cadena que le pasa, reemplaza todos los espacios con guiones ( `-`) y se asegura de que todo esté en minúsculas. Esto te deja con un bonito slug, perfecto para crear URI.
        
        ### **Volver a la página de éxito[](https://codeigniter.com/user_guide/tutorial/create_news_items.html#return-success-page)**
        
        Después de esto, los archivos de visualización se cargan y se devuelven para mostrar un mensaje de éxito. Cree una vista en **app/Views/news/success.php** y escriba un mensaje de éxito.
        
        Esto podría ser tan simple como:
        
        **`<**p**>**News item created successfully**.</**p**>**`
        
6. Configurar el **Modelo** para permitir que los datos se guarden correctamente. 
    
    Lo único que queda es asegurarse de que su modelo esté configurado para permitir que los datos se guarden correctamente. El `save()`método que se utilizó determinará si se debe insertar la información o si la fila ya existe y se debe actualizar, en función de la presencia de una clave primaria. En este caso, no se `id`le pasa ningún campo, por lo que insertará una nueva fila en su tabla `news`.
    
    Sin embargo, de forma predeterminada, los métodos de inserción y actualización en el modelo en realidad no guardarán ningún dato porque no sabe qué campos es seguro actualizar. Edite `NewsModel`para proporcionarle una lista de campos actualizables en la `$allowedFields`propiedad.
    
    ```php
    <?php
    
    namespace App\Models;
    
    use CodeIgniter\Model;
    
    class NewsModel extends Model
    {
        protected $table = 'news';
    
        protected $allowedFields = ['title', 'slug', 'body'];
    }
    ```
    
    Esta nueva propiedad ahora contiene los campos que permitimos guardar en la base de datos. Observe que omitimos el `id`? Esto se debe a que casi nunca necesitarás hacerlo, ya que es un campo que se incrementa automáticamente en la base de datos. Esto ayuda a proteger contra vulnerabilidades de asignaciones masivas. Si su modelo maneja sus marcas de tiempo, también las omitirá.