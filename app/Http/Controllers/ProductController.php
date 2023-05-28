<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        // $query = Product::query();
        $query = Product::with([
            'productVariantPrices',
            'productVariantPrices.productVariantOne',
            'productVariantPrices.productVariantTwo',
            'productVariantPrices.productVariantThree'
        ]);

          // Apply filters if provided
        if (!empty($request->title)) {
            $query->where('title','like', '%' . $request->input('title') . '%');
        }elseif(!empty($request->date)) {
            $fromDate = $request->date;
            $query->whereDate('created_at', $fromDate);
        }elseif(!empty($request->price_from)){
            $fromPrice = $request->price_from;
            $query->whereHas('productVariantPrices', function ($q) use ($fromPrice) {
                $q->where('price', '>=', $fromPrice);
            });
        }elseif(!empty($request->price_to)){
            $toPrice = $request->price_to;
            $query->whereHas('productVariantPrices', function ($q) use ($toPrice) {
                $q->where('price', '<=', $toPrice);
            });
        }

        $products = $query->paginate(5);
        
        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeProductImage(Request $request)
    {
        $image = $request->file('file');
        $imageName = $image->getClientOriginalName();
        $image->move(public_path('images'), $imageName);
        return response()->json(['success' => $imageName]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // return $request->all();

        DB::beginTransaction();

        try {
            // build product model data
            $product              = new Product;
            $product->title       = $request->product_name;
            $product->sku         = $request->product_sku;
            $product->description = $request->product_description;
            $product->save();

            // build proudct image model data
            $imageArr = json_decode($request->get('files'), true);

            foreach ($imageArr as $key => $image) {
                $productImage               = new ProductImage;
                $productImage->product_id   = $product->id;
                $productImage->file_path    = sprintf('images/%s', $image);
                $productImage->thumbnail    = !$key;
                $productImage->save();
            }

            // build product variants model data
            $insertedVariantIds = [];
            $variantsArr = $request->product_variant;
            $variantOptionArr = array_column($variantsArr, 'option');

            foreach ($variantsArr as $variantArr) {
                $variantOption = $variantArr['option'];
                $variantValueArr = $variantArr['value'];

                foreach ($variantValueArr as $variant) {
                    $productVariant               = new ProductVariant;
                    $productVariant->variant      = $variant;
                    $productVariant->variant_id   = $variantOption;
                    $productVariant->product_id   = $product->id;
                    $productVariant->save();
                    
                    $insertedVariantIds[$variantOption][$variant] = $productVariant->id;
                }
            }

            foreach ($request->product_preview as $preview) {
                $variantProperties = ['product_variant_one', 'product_variant_two', 'product_variant_three'];
                $currentVariantArr = array_combine($variantOptionArr, array_filter(explode('/', $preview['variant'])));
                
                $productVariantPrice = new ProductVariantPrice;
                $productVariantPrice->product_variant_one = null;
                $productVariantPrice->product_variant_two = null;
                $productVariantPrice->product_variant_three = null;

                foreach ($currentVariantArr as $ckey => $currentVariant) {
                    $currentProperty = current($variantProperties);
                    $productVariantPrice->{$currentProperty} = $insertedVariantIds[$ckey][$currentVariant];
                    array_shift($variantProperties);
                }

                $productVariantPrice->price = $preview['price'];
                $productVariantPrice->stock = $preview['stock'];
                $productVariantPrice->product_id = $product->id;
                $productVariantPrice->save();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
        }

        DB::commit();
        return redirect()->route('product.index');
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {


    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $variants = Variant::all();
        return view('products.edit', compact('variants'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
