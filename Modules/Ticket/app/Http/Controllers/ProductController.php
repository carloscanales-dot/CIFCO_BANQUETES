<?php

namespace Modules\Ticket\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Ticket\Models\Product;
use Modules\Ticket\Http\Requests\ProductRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

use Modules\Ticket\Traits\SetFilterQuery;

class ProductController extends Controller
{
    use SetFilterQuery;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DB::table('products')->when($request->get('search'), function ($query, $search) {
            return $query->where(function ($query) use ($search) {
                foreach ($search as $field => $value) {
                    $filter = $this->setField($field);

                    if (!is_null($filter) && !is_null($value)) {
                        $this->setFilter($query, $filter['operator'], $filter['field'], $value);
                    }
                }
            });
        })->when($request->get('sort'), function ($query, $sortBy) {
            return $query->orderBy($sortBy['key'], $sortBy['order']);
        });

        $result = $query
            ->select('product_id', 'prefix', 'product_name', 'unit_price', 'status')
            ->paginate($request->get('limit', 10));

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return Inertia::render('Ticket/Product/Index', [
            'result' => $result
        ]);
    }

    /**
     * List categories load resource
     */
    public function list($status)
    {
        $products =  DB::table('products')
            ->where(function ($query) use ($status) {
                if ($status === 'Activo') {
                    $query->where('status', $status);
                }
            })
            ->select('product_id', 'product_name')
            ->orderBy('product_name', 'asc')
            ->get();

        return response()->json(['products' => $products]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Ticket/Product/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request)
    {
        $product = $this->setDataStore($request);
        $productId = DB::table('products')->insertGetId($product);

        $message = sprintf('El producto %s, ha sido ingresada exitosamente.', $product['product_name']);

        if ($request->expectsJson()) {
            if ($productId) {
                $newProduct = (object) $product;
                $newProduct->product_id = $productId;
            }

            return response()->json([
                'message' => $message,
                'product' => $newProduct
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product): Response
    {
        return Inertia::render('Ticket/Product/Edit', [
            'product' => $product
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, Product $product)
    {
        $product->update($request->all());

        return redirect()->back()->with('success', sprintf('Actualizado con éxito, el producto %s', $product->product_name));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->back()->with('success', sprintf('Eliminado con éxito, el producto %s', $product->product_name));
    }

    protected function setDataStore($request)
    {
        $current_date = $this->getCurrentDate()->format('Y-m-d H.i:s');

        return [
            'prefix' => strtoupper($request->get('prefix')),
            'product_name' => $request->get('product_name'),
            'unit_price' => $request->get('unit_price'),
            'status' => $request->get('status'),
            'created_at' => $current_date,
            'updated_at' => $current_date
        ];
    }

    protected function getCurrentDate()
    {
        return Carbon::now()->setTimezone(config('app.timezone'));
    }

    /**
     * @param type $field
     * @return type
     */
    protected function setField($field): array
    {
        $fieldList = [
            'product_name' => [
                'field' => 'product_name',
                'operator' => 'like'
            ],
            'uuid' => [
                'field' => 'uuid',
                'operator' => 'like'
            ],
            'status' => [
                'field' => 'status',
                'operator' => 'equal'
            ]
        ];

        return $fieldList[$field] ?? null;
    }
}
