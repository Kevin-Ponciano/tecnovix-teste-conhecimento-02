<?php

namespace App\Http\Controllers\Admin;

//use App\Http\Requests\LivroRequest;
use App\Http\Requests\CreateRequest;
use App\Http\Requests\UpdateRequest;
use App\Models\Endereco;
use App\Models\Livro;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use function Psy\debug;

/**
 * Class LivroCrudController
 * @package App\Http\Controllers\Admin
 * @property-read CrudPanel $crud
 */
class LivroCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;
    use ShowOperation;

    public function searchBookAPI($isbn)
    {
        $response = HTTP::withOptions([
            'verify' => storage_path('app/certificados/GTS Root R1.crt'),
        ])->get("https://www.googleapis.com/books/v1/volumes?q=isbn:$isbn");

        if ($response['totalItems'] != 1) {
            $validator = Validator::make([], []);
            $validator->errors()->add('isbn', 'O ISBN informado é inválido.');
            throw new ValidationException($validator);
        } else {
            return $response['items'][0]['volumeInfo'];
        }
    }


    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(Livro::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/livro');
        CRUD::setEntityNameStrings('livro', 'livros');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::setFromDb();
        CRUD::column('endereco_id')->remove();
    }

    protected function setupShowOperation()
    {
        $this->autoSetupShowOperation();
        CRUD::column('endereco_id')->remove();
        CRUD::column('capa')->type('image')->upload(false)->disk('s3')->temporary(true);
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     * @throws Exception
     */
    protected function setupCreateOperation()
    {

        $this->crud->setValidation(CreateRequest::class);
        CRUD::setFromDb();
        CRUD::field('endereco.cep')->type('text')->label('CEP')->wrapper(['class' => 'form-group col-md-3']);
        CRUD::field('endereco.rua')->type('text')->label('Rua')->wrapper(['class' => 'form-group col-md-7']);
        CRUD::field('endereco.numero')->type('text')->label('Número')->wrapper(['class' => 'form-group col-md-2']);
        CRUD::field('endereco.bairro')->type('text')->label('Bairro')->wrapper(['class' => 'form-group col-md-5']);
        CRUD::field('endereco.cidade')->type('text')->label('Cidade')->wrapper(['class' => 'form-group col-md-5']);
        CRUD::field('endereco.uf')->type('text')->label('UF')->wrapper(['class' => 'form-group col-md-2']);

        CRUD::field('titulo')->remove();
        CRUD::field('autor')->remove();
        CRUD::field('editora')->remove();
        CRUD::field('ano_de_publicacao')->remove();
        CRUD::field('endereco_id')->remove();
        CRUD::field('descricao')->remove();
        CRUD::field('paginas')->remove();
        CRUD::field('capa')->type('upload')->withFiles(['disk' => 's3', 'path' => 'capas'])
            ->label('Capa do Livro')->wrapper(['class' => 'form-group col-md-12']);

        Livro::creating(function ($entry) {
            $endereco = Endereco::create([
                'cep' => request()->get('endereco')['cep'],
                'rua' => request()->get('endereco')['rua'],
                'numero' => request()->get('endereco')['numero'],
                'bairro' => request()->get('endereco')['bairro'],
                'cidade' => request()->get('endereco')['cidade'],
                'uf' => request()->get('endereco')['uf'],
            ]);
            $isbn = $entry->isbn;
            $livro = $this->searchBookAPI($isbn);
            $entry->titulo = $livro['title'] ?? 'Não informado';
            $entry->autor = $livro['authors'][0] ?? 'Não informado';
            $entry->editora = $livro['publisher'] ?? 'Não informado';
            $entry->ano_de_publicacao = $livro['publishedDate'] ?? 'Não informado';
            $entry->descricao = $livro['description'] ?? 'Não informado';
            $entry->paginas = $livro['pageCount'] ?? 'Não informado';
            $entry->endereco_id = $endereco->id;
        });
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->crud->setValidation(UpdateRequest::class);
        CRUD::setFromDb();

        $endereco = Endereco::find($this->crud->getCurrentEntry()->endereco_id);
        $this->crud->addField([
            'name' => 'endereco.cep',
            'type' => 'text',
            'label' => 'CEP',
            'value' => $endereco->cep,
            'wrapper' => ['class' => 'form-group col-md-3']
        ]);
        $this->crud->addField([
            'name' => 'endereco.rua',
            'type' => 'text',
            'label' => 'Rua',
            'value' => $endereco->rua,
            'wrapper' => ['class' => 'form-group col-md-7']
        ]);
        $this->crud->addField([
            'name' => 'endereco.numero',
            'type' => 'text',
            'label' => 'Número',
            'value' => $endereco->numero,
            'wrapper' => ['class' => 'form-group col-md-2']
        ]);
        $this->crud->addField([
            'name' => 'endereco.bairro',
            'type' => 'text',
            'label' => 'Bairro',
            'value' => $endereco->bairro,
            'wrapper' => ['class' => 'form-group col-md-5']
        ]);
        $this->crud->addField([
            'name' => 'endereco.cidade',
            'type' => 'text',
            'label' => 'Cidade',
            'value' => $endereco->cidade,
            'wrapper' => ['class' => 'form-group col-md-5']
        ]);
        $this->crud->addField([
            'name' => 'endereco.uf',
            'type' => 'text',
            'label' => 'UF',
            'value' => $endereco->uf,
            'wrapper' => ['class' => 'form-group col-md-2']
        ]);
        $this->crud->addField([
            'name' => 'capa',
            'type' => 'upload',
            'label' => 'Capa do Livro',
            'upload' => true,
            'disk' => 's3',
            'path' => 'capas',
            'temporary' => true,
            'wrapper' => ['class' => 'form-group col-md-12']
        ]);

        CRUD::field('isbn')->attributes(['disabled' => 'disabled']);
        CRUD::field('endereco_id')->remove();

        Livro::saving(function () use ($endereco) {
            $endereco->cep = request()->get('endereco')['cep'];
            $endereco->rua = request()->get('endereco')['rua'];
            $endereco->numero = request()->get('endereco')['numero'];
            $endereco->bairro = request()->get('endereco')['bairro'];
            $endereco->cidade = request()->get('endereco')['cidade'];
            $endereco->uf = request()->get('endereco')['uf'];
            $endereco->save();
        });
    }

}
