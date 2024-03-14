<?php

namespace App\Controllers;

use App\Models\NewsModel;

class News extends BaseController
{
    public function index()
    {
        $model = new NewsModel();

        $data = [
            'news'  => $model->getNews(),
            'title' => 'News archive',
        ];

        echo view('templates/header', $data);
        echo view('news/index', $data);
        echo view('templates/footer');
    }

    public function show($slug = null)
    {
        $model = new NewsModel();

        $data['news'] = $model->getNews($slug);

        if (empty($data['news'])) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Cannot find the news item: ' . $slug);
        }

        $data['title'] = $data['news']['title'];

        echo view('templates/header', $data);
        echo view('news/view', $data);
        echo view('templates/footer');
    }

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