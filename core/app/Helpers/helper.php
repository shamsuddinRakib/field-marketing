<?php

use App\Models\backend\FiscalYear;
use App\Models\backend\JournalEntry;
use App\Models\backend\Offer;
use App\Models\backend\OfferDiscount;
use App\Models\backend\OfferDiscountProduct;
use App\Models\backend\OfferGift;
use App\Models\backend\OfferShipping;
use App\Models\backend\Upazila;
use App\Models\backend\VoucherType;
use App\Models\Coupon;
use App\Models\Option;
use App\Models\Permission;
use App\Models\Product;
use App\Support\BranchScope;
use App\Support\WarehouseScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;


if (! function_exists('get_option')) {

    function get_option($name)
    {
        if (! empty($name)) {
            $option = Option::where("name", "=", $name)->first();
            if ($option) {
                return $option->value;
            } else {
                return '';
            }
        } else {
            return '';
        }
    }
}

if (! function_exists('get_client_ip')) {

    /**
     * get_client_ip
     *
     * @return string
     */
    function get_client_ip()
    {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP')) {
            $ipaddress = getenv('HTTP_CLIENT_IP');
        } else if (getenv('HTTP_X_FORWARDED_FOR')) {
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        } else if (getenv('HTTP_X_FORWARDED')) {
            $ipaddress = getenv('HTTP_X_FORWARDED');
        } else if (getenv('HTTP_FORWARDED_FOR')) {
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        } else if (getenv('HTTP_FORWARDED')) {
            $ipaddress = getenv('HTTP_FORWARDED');
        } else if (getenv('REMOTE_ADDR')) {
            $ipaddress = getenv('REMOTE_ADDR');
        } else {
            $ipaddress = 'UNKNOWN';
        }

        return $ipaddress;
    }
}

if (! function_exists('uploadFile')) {
    function uploadFile($file, $directory)
    {
        if ($file) {
            $uniqueId  = uniqid();
            $fileName  = $uniqueId . '.' . $file->getClientOriginalExtension();

            // Relative path for database
            $relPath = "uploads/$directory/" . date("Y/m/d/");

            // Absolute path for saving (outside core)
            $absPath = base_path('../' . $relPath);

            if (! file_exists($absPath)) {
                mkdir($absPath, 0755, true);
            }

            $file->move($absPath, $fileName);
            return $relPath . $fileName;
        }
        return null;
    }
}

if (! function_exists('image')) {
    function image($src, $cdn = false)
    {
        if (empty($src) || ! file_exists($src)) {
            return asset('theme/frontend/assets/img/default/book.png');
        }

        return asset($src);
    }
}

if (! function_exists('is_active')) {

    function is_active($mainString, $prefix)
    {
        // Extracting the prefix before the dot
        $prefixFromMainString = substr($mainString, 0, strpos($mainString, '.'));

        // Customizing the regular expression pattern
        $pattern = '/^' . preg_quote($prefix, '/') . '/';

        // Testing with preg_match
        return (bool) preg_match($pattern, $prefixFromMainString);
    }
}

if (! function_exists('can_view')) {
    function can_view($url)
    {

        $site_url = get_option('site_url') . "/";
        $url      = str_replace("www.", "", $url);
        //dd($site_url);
        $currentPath = str_replace($site_url, "", $url);
        $user_role   = Auth::user()->user_role;
        //super
        if ($user_role == 1) {
            return true;
        }

        ///$role = Role::whereId($user_role)->first();
        $permission = explode(",", Auth::user()->user_permission);

        foreach ($permission as $single) {

            $slug = Permission::whereId($single)->first();
            if ($slug) {

                if (in_array($currentPath, explode(",", $slug->slug))) {
                    return true;
                }
            }
        }

        return false;
    }
}

if (! function_exists('resizeImage')) {

    function resizeImage($filePath, $height, $width, $format)
    {
        list($originalWidth, $originalHeight) = getimagesize($filePath);

        if ($height == 0 || $width == 0) {
            $height = $originalHeight;
            $width  = $originalWidth;
        } else {
            // Calculate new dimensions
            $aspectRatio = $originalWidth / $originalHeight;
            if ($width / $height > $aspectRatio) {
                $width = $height * $aspectRatio;
            } else {
                $height = $width / $aspectRatio;
            }
        }

        // Create a new true color image with new dimensions
        $newImage = imagecreatetruecolor($width, $height);

        // Create image resource based on the format
        switch ($format) {
            case 'jpeg':
            case 'jpg':
                $imageResource = imagecreatefromjpeg($filePath);
                break;
            case 'png':
                $imageResource = imagecreatefrompng($filePath);
                break;
            case 'webp':
                $imageResource = imagecreatefromjpeg($filePath);
                break;
            default:
                throw new \Exception("Unsupported format: $format");
        }

        // Copy and resize old image into new image
        imagecopyresampled($newImage, $imageResource, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);

        // Save the new image
        switch ($format) {
            case 'jpeg':
            case 'jpg':
                imagejpeg($newImage, $filePath);
                break;
            case 'png':
                imagepng($newImage, $filePath);
                break;
            case 'webp':
                imagewebp($newImage, $filePath);
                break;
            default:
                throw new \Exception("Unsupported format: $format");
        }

        // Free up memory
        imagedestroy($newImage);
        imagedestroy($imageResource);
    }
}

/*if (!function_exists('uploadImage')) {
    function uploadImage($file, $directory, $watermarkPath = null, $quality = 60)
    {
        if ($file) {
            // Generate a unique filename using time and a random number
            $uniqueId = uniqid(); // Generates a unique ID based on the current time in microseconds
            $imageName = $uniqueId . '.webp'; // Fixed extension to webp
            $imagePath = "uploads/$directory/" . date("Y/m/d/");

            // 0755 permissions allow the owner full control over the directory,
            // while group members and others have read and execute permissions only.
            if (!file_exists($imagePath)) {
                mkdir($imagePath, 0755, true);
            }

            // Move the uploaded file to the designated directory
            $file->move($imagePath, $imageName);

            //dd($imagePath . $imageName);

            // Load the uploaded image
            $image = imagecreatefromstring(file_get_contents($imagePath . $imageName));
           // $image = imagecreatefromwebp($imagePath . $imageName);

            // If a watermark is provided, add it to the image
            if ($watermarkPath) {
                // Load the watermark image
                $watermark = imagecreatefrompng($watermarkPath);

                // Get dimensions of the watermark
                $watermarkWidth = imagesx($watermark);
                $watermarkHeight = imagesy($watermark);

                // Calculate position for the watermark (center of the image)
                $x = (imagesx($image) - $watermarkWidth) / 2;
                $y = (imagesy($image) - $watermarkHeight) / 2;

                // Merge the watermark onto the image
                imagecopy($image, $watermark, $x, $y, 0, 0, $watermarkWidth, $watermarkHeight);

                // Free memory for the watermark
                imagedestroy($watermark);
            }

            // Save the final image with the specified quality
            imagewebp($image, $imagePath . $imageName, $quality);

            // Free memory
            imagedestroy($image);

            return $imagePath . $imageName;
        }
        return null;
    }
}*/

if (! function_exists('uploadImage')) {
    function uploadImage($file, $directory, $watermarkPath = null, $quality = 60)
    {
        if ($file) {
            // Generate a unique filename using time and a random number
            $uniqueId  = uniqid();            // Generates a unique ID based on the current time in microseconds
            $imageName = $uniqueId . '.webp'; // Fixed extension to webp
            $imagePath = "uploads/$directory/" . date("Y/m/d/");

            // 0755 permissions allow the owner full control over the directory,
            // while group members and others have read and execute permissions only.
            if (! file_exists($imagePath)) {
                mkdir($imagePath, 0755, true);
            }

            // Move the uploaded file to the designated directory
            $file->move($imagePath, $imageName);

            // Load the uploaded image with ImageMagick
            $originalFilePath = $imagePath . $imageName;
            $outputPath       = $imagePath . $imageName; // Output path for the converted WebP image

            // Use ImageMagick to convert the image to WebP
            exec("convert $originalFilePath -quality $quality $outputPath");

            // If a watermark is provided, add it to the image
            if ($watermarkPath && file_exists($watermarkPath)) {
                // Load the watermark image
                $watermark       = new Imagick($watermarkPath);
                $watermarkWidth  = $watermark->getImageWidth();
                $watermarkHeight = $watermark->getImageHeight();

                // Create an Imagick object from the converted image
                $image = new Imagick($outputPath);

                // Calculate position for the watermark (center of the image)
                $x = ($image->getImageWidth() - $watermarkWidth) / 2;
                $y = ($image->getImageHeight() - $watermarkHeight) / 2;

                // Composite the watermark onto the image
                $image->compositeImage($watermark, Imagick::COMPOSITE_OVER, $x, $y);

                // Save the final image
                $image->writeImage($outputPath);

                // Clean up
                $image->destroy();
                $watermark->destroy();
            }

            return $outputPath; // Return the path of the saved WebP image
        }
        return null;
    }
}

/*if (!function_exists('uploadImages')) {
    function uploadImages($image, $format, $path, $height, $width)
    {
        if ($image) {
            // Create the directory path
            $directoryPath = $path . date("Y") . "/" . date("m") . "/" . date("d") . "/";
            if (!Storage::exists($directoryPath)) {
                Storage::makeDirectory($directoryPath);
            }

            // Generate the image name
            $imageName = uniqid() . time() . '.' . $format;
            $imagePath = $directoryPath . $imageName;

            // Move the uploaded image to the target directory
            $image->move($directoryPath, $imageName);
            // Resize the image
            resizeImage($imagePath, $height, $width, $format);

            return $imagePath;
        }

        return null;
    }
}*/

if (! function_exists('uploadImages')) {
    function uploadImages($image, $format, $path, $height = null, $width = null)
    {
        if ($image) {
            // Create the directory path
            $directoryPath = $path . date("Y") . "/" . date("m") . "/" . date("d") . "/";
            if (! Storage::exists($directoryPath)) {
                Storage::makeDirectory($directoryPath);
            }

            // Generate the image name
            $imageName = uniqid() . time() . '.' . $format;
            $imagePath = $directoryPath . $imageName;

            // Move the uploaded image to the target directory
            $image->move($directoryPath, $imageName);

            // Return the path to the uploaded image
            return $imagePath;
        }

        return null;
    }
}

if (! function_exists('UserID')) {

    function UserID()
    {
        return (Auth::check()) ? Auth::user()->id : 0;
    }
}

if (! function_exists('formatPrice')) {

    function formatPrice($price)
    {
        if (is_numeric($price)) {
            return '৳ ' . number_format($price, 2);
        } else {
            return '৳ 0.00'; // Return a default price if the input is invalid
        }
    }
}

if (! function_exists('generateOrderStatusBadge')) {
    function generateOrderStatusBadge($orderStatusId)
    {
        // Define status colors mapping
        $statusColors = [
            1 => ['label' => 'Pending', 'class' => 'bg-warning'],
            2 => ['label' => 'Confirmed', 'class' => 'bg-secondary'],
            3 => ['label' => 'Packing', 'class' => 'bg-info'],
            4 => ['label' => 'On the way', 'class' => ' badge rounded-pill bg-soft-info'],
            5 => ['label' => 'Cancelled', 'class' => 'bg-danger'],
            6 => ['label' => 'Return', 'class' => 'bg-dark text-white'],
            7 => ['label' => 'Completed', 'class' => 'badge-bgsuccess'],
            8 => ['label' => 'Hold', 'class' => 'badge rounded-pill bg-soft-danger'],
        ];

        // Check if the status ID exists in the colors mapping
        if (isset($statusColors[$orderStatusId])) {

            $status = $statusColors[$orderStatusId];

            return '<span class="badge rounded-pill   ' . $status['class'] . '">' . $status['label'] . '</span>';
        }
        return '';
    }
}

if (! function_exists('numberToWords')) {
    function numberToWords($number)
    {
        $hyphen      = '-';
        $conjunction = ' and ';
        $separator   = ', ';
        $negative    = 'negative ';
        $decimal     = ' point ';
        $dictionary  = [
            0                   => 'zero',
            1                   => 'one',
            2                   => 'two',
            3                   => 'three',
            4                   => 'four',
            5                   => 'five',
            6                   => 'six',
            7                   => 'seven',
            8                   => 'eight',
            9                   => 'nine',
            10                  => 'ten',
            11                  => 'eleven',
            12                  => 'twelve',
            13                  => 'thirteen',
            14                  => 'fourteen',
            15                  => 'fifteen',
            16                  => 'sixteen',
            17                  => 'seventeen',
            18                  => 'eighteen',
            19                  => 'nineteen',
            20                  => 'twenty',
            30                  => 'thirty',
            40                  => 'forty',
            50                  => 'fifty',
            60                  => 'sixty',
            70                  => 'seventy',
            80                  => 'eighty',
            90                  => 'ninety',
            100                 => 'hundred',
            1000                => 'thousand',
            1000000             => 'million',
            1000000000          => 'billion',
            1000000000000       => 'trillion',
            1000000000000000    => 'quadrillion',
            1000000000000000000 => 'quintillion',
        ];

        if (! is_numeric($number)) {
            return false;
        }

        if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
            // overflow
            trigger_error(
                'convert() only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
                E_USER_WARNING
            );
            return false;
        }

        if ($number < 0) {
            return $negative . numberToWords(abs($number));
        }

        $string = $fraction = null;

        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens   = ((int) ($number / 10)) * 10;
                $units  = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $hyphen . $dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds  = $number / 100;
                $remainder = $number % 100;
                $string    = $dictionary[$hundreds] . ' ' . $dictionary[100];
                if ($remainder) {
                    $string .= $conjunction . numberToWords($remainder);
                }
                break;
            default:
                $baseUnit     = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder    = $number % $baseUnit;
                $string       = numberToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= numberToWords($remainder);
                }
                break;
        }

        if (null !== $fraction && is_numeric($fraction)) {
            $string .= $decimal;
            $words   = [];
            foreach (str_split((string) $fraction) as $number) {
                $words[] = $dictionary[$number];
            }
            $string .= implode(' ', $words);
        }

        return $string;
    }
}

// if (!function_exists('productSection')) {
//     function productSection($type)
//     {
//         if ($type == 'recent') {
//             // Retrieve the 10 most recent products
//             $latestProducts = Product::orderBy('created_at', 'desc')->take(10)->get();
//             return $latestProducts;
//         }

//         return collect(); // Return an empty collection if the type doesn't match
//     }
// }

if (! function_exists('sendMail')) {
    function sendMail($otp, $email, $name)
    {

        $data = [
            'otp'  => $otp,
            'name' => $name,
        ];

        $result = Mail::to($email)->send(new \App\Mail\OtpEmail($data));
        return $result;
    }
}

// //updated
// if (!function_exists('productSection')) {
//     function productSection($type)
//     {
//         if ($type == 'recent') {

//             //$latestProducts = \App\Models\Product::where('product_type', //'book')->orderBy('created_at', 'desc')->take(10)->get();

//             // Get the product with id = 1
//           $featuredProduct = \App\Models\Product::find([1, 2, 3]);

//             // Get the latest products excluding the one with id = 1
//             $latestProducts = \App\Models\Product::where('product_type', 'book')
//                 ->whereNotIn('id', [1, 2, 3])
//                 ->orderBy('created_at', 'desc')
//                 ->take(7)
//                 ->get()
//                 ->sortBy('stock_status');

//             // Combine the featured product with the latest products
//             $finalProducts = $latestProducts->prepend($featuredProduct);

//             return  $finalProducts;
//         }

//         return collect();
//     }
// }

if (! function_exists('productSection')) {
    function productSection($type)
    {
        if ($type == 'recent') {
            // Get the featured products with ids 1, 2, 3
            $featuredProducts = \App\Models\Product::whereIn('id', [1, 2, 3])->get();

            // Get the latest products excluding the featured ones
            $latestProducts = \App\Models\Product::where('product_type', 'book')
                ->whereNotIn('id', [1, 2, 3])
                //->where('stock_status','in_stock')
                ->orderBy('created_at', 'desc')
                ->take(7)
                ->get()                   // Fetch the results as a collection
                ->sortBy('stock_status'); // Sort the collection by stock_status

            // Combine the featured products with the latest products
            $finalProducts = $featuredProducts->merge($latestProducts);

            return $finalProducts;
        }

        return collect();
    }
}


if (! function_exists('priceAfterDiscount')) {
    function priceAfterDiscount($product)
    {
        if (! $product instanceof \App\Models\backend\Product) {
            throw new InvalidArgumentException('Expected an instance of Product model.');
        }

        $currentPrice   = $product->current_price;
        $mrpPrice       = $product->mrp_price;
        $discountType   = $product->discount_type;
        $discountAmount = $product->discount_amount;

        // Check if there is an active campaign
        $hasActiveCampaign = $product->hasActiveCampaign();
        $activeCampaign    = $hasActiveCampaign ? $product->getActiveCampaign() : null;

        $existingDiscount = 0;

        // Calculate existing discount based on product discount type
        if ($discountType == 'percentage') {
            $existingDiscount = ($discountAmount / 100) * $mrpPrice;
        } elseif ($discountType == 'amount') {
            $existingDiscount = $discountAmount;
        }

        // Calculate final discounted price including campaign discount if applicable
        if ($hasActiveCampaign && $activeCampaign) {

            if ($activeCampaign->discount_type->value == 'percent') {

                $discount = ($currentPrice / 100) * $activeCampaign->discount;

                $campaignDiscountAmount = $discount;
                //$campaignDiscountAmount = ($activeCampaign->discount / 100) * $currentPrice;
            } else {
                $campaignDiscountAmount = $activeCampaign->discount;
            }

            $finalDiscountedPrice = $mrpPrice - ($existingDiscount + $campaignDiscountAmount);
        } else {
            $finalDiscountedPrice = $mrpPrice - $existingDiscount;
        }

        // Ensure final price doesn't go below zero
        return max($finalDiscountedPrice, 0);
    }
}

if (! function_exists('calculateDiscount')) {

    function calculateDiscount($product)
    {
        $mrpPrice             = $product->mrp_price;
        $finalDiscountedPrice = priceAfterDiscount($product);

        $discountAmount     = $mrpPrice - $finalDiscountedPrice;
        $discountPercentage = ($discountAmount / $mrpPrice) * 100;

        return [
            'discountAmount'     => round($discountAmount),
            'discountPercentage' => round($discountPercentage),
        ];
    }
}

if (! function_exists('calculateCouponDiscount')) {

    function calculateCouponDiscount($cartSubtotal, $coupon, $cartItems)
    {
        $couponDiscount = 0;

        if ($coupon->c_type === 'cart_base') {

            // Check cart subtotal for cart_base coupon
            if ($cartSubtotal < $coupon->min_buy) {
                $difference = $coupon->min_buy - $cartSubtotal;
                return ['error' => 'Cart subtotal is less than the minimum buy value. You need an additional ৳' . $difference . ' to apply this coupon.', 'status' => 422];
            }

            // Calculate the discount
            if ($coupon->discount_type === 'amount') {
                $couponDiscount = $coupon->discount;
                if ($couponDiscount > $coupon->max_discount) {
                    $couponDiscount = $coupon->max_discount;
                }
            } elseif ($coupon->discount_type === 'percent') {
                $couponDiscount = ($cartSubtotal * ($coupon->discount / 100));
                if ($couponDiscount > $coupon->max_discount) {
                    $couponDiscount = $coupon->max_discount;
                }
            }
        } elseif ($coupon->c_type === 'product_base') {

            $couponProducts = $coupon->products;

            $matchingProducts         = [];
            $matchingProductsSubtotal = 0;

            foreach ($cartItems as $item) {

                if ($couponProducts->contains($item['id'])) {

                    $matchingProducts[] = $item['id'];

                    $matchingProductsSubtotal += $item['current_price'] * $item['quantity'];
                }
            }

            if (empty($matchingProducts)) {
                return ['error' => 'Add exact item(s) in cart to apply this coupon.', 'status' => 422];
            }

            // Calculate the discount for matching products
            if ($coupon->discount_type === 'amount') {
                $couponDiscount = $coupon->discount;
                if ($couponDiscount > $coupon->max_discount) {
                    $couponDiscount = $coupon->max_discount;
                }
                if ($couponDiscount > $matchingProductsSubtotal) {
                    $couponDiscount = $matchingProductsSubtotal;
                }
            } elseif ($coupon->discount_type === 'percent') {

                $couponDiscount = ($matchingProductsSubtotal * ($coupon->discount / 100));

                if ($couponDiscount > $coupon->max_discount) {
                    $couponDiscount = $coupon->max_discount;
                }
            }
        }

        return ['discount_amount' => number_format($couponDiscount, 2), 'status' => 200];
    }
}

function hasActiveCoupons()
{
    return Coupon::where('end_date', '>', now())->count() > 0;
}

if (! function_exists('calculateAverageRating')) {
    function calculateAverageRating($item)
    {

        $totalReviews = $item->reviews()->count();

        if ($totalReviews === 0) {
            return 0;
        }

        $sumRatings    = $item->reviews()->sum('rating');
        $averageRating = $sumRatings / $totalReviews;

        return round($averageRating, 1);
    }
}

function replaceQuotes($text)
{
    // Initialize a flag to alternate between opening and closing quotes
    $isOpeningQuote = true;

    // Use a callback function with preg_replace_callback
    $result = preg_replace_callback('/"/', function ($matches) use (&$isOpeningQuote) {
        if ($isOpeningQuote) {
            $isOpeningQuote = false;
            return '“'; // Opening quote
        } else {
            $isOpeningQuote = true;
            return '”'; // Closing quote
        }
    }, $text);

    return $result;
}

function convertToBengaliNumber($number)
{

    $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $bengaliNumbers = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

    return str_replace($englishNumbers, $bengaliNumbers, $number);
}

if (! function_exists('isProductInWishlist')) {
    /**
     * Check if a product is in the authenticated user's wishlist.
     *
     * @param int $productId
     * @return bool
     */
    function isProductInWishlist($productId)
    {
        if (Auth::check()) {
            // Check if the product exists in the user's wishlist
            return Auth::user()->wishlist()->where('product_id', $productId)->exists();
        }
        return false;
    }
}

if (! function_exists('getCartWeightBasedShipping')) {
    /**
     * Calculate the total cart weight and corresponding shipping charge.

     * @return int Calculated shipping charge based on the weight.
     */
    function getCartWeightBasedShipping()
    {
        // Retrieve the cart from the session
        $baseCharge = get_option('shipping_charge') ?? 60;

        $cart = Session::get('cart', []); // Default to an empty array if 'cart' does not exist

        // Initialize total weight
        $totalWeight = 0;

        // Check if the cart contains items
        if (isset($cart['items']) && is_array($cart['items'])) {
            foreach ($cart['items'] as $item) {
                // Find the product by ID
                $product = Product::find($item['id']);

                if ($product) {
                    if ($product->isBundle == 1) {
                        // Calculate bundle weight
                        $bundleWeight = 0;

                        foreach ($product->bundleProducts as $bundleProduct) {
                            $bundleItem = Product::find($bundleProduct->bundle_product_id);
                            if ($bundleItem) {
                                $bundleWeight += ($bundleItem->weight ?? 0) * $bundleProduct->quantity;
                            }
                        }

                        $totalWeight += $bundleWeight * $item['quantity']; // Multiply by cart quantity
                    } else {
                        // Handle non-bundle product
                        $weight       = $product->weight ?? 0;       // Default to 0 if weight is null
                        $totalWeight += $weight * $item['quantity']; // Multiply by cart quantity
                    }
                }
            }
        }

        // // Add 10% of the total weight as packaging weight
        // $totalWeight += ($totalWeight * 0.1);

        // Convert total weight to kilograms and round up
        $weightInKg = ceil($totalWeight / 1000);

        // Calculate the total charge
        $extraChargePerKg = 20;
        if ($weightInKg <= 1) {
            return $baseCharge; // Base charge for up to 1 kg
        }

        // Charge for additional kilograms
        $additionalCharge = ($weightInKg - 1) * $extraChargePerKg;

        return $baseCharge + $additionalCharge;
    }

    // Get current branch id
    function current_branch_id(): ?int
    {
        return BranchScope::currentId();
    }
    // Get current warehouse id
    function current_warehouse_id(): ?int
    {
        return WarehouseScope::get();
    }

    // Get current fiscal year(according to is_active field in fiscal_years table)
    if (! function_exists('currentFiscalYear')) {

        function currentFiscalYear()
        {
            return FiscalYear::where('is_active', 1)->first();
        }
    }

    function requireFiscalYear()
    {
        $fy = currentFiscalYear();

        if (! $fy) {
            abort(503, 'Fiscal year is not configured.');
        }

        return $fy;
    }

    //voucher number genarate function
    if (! function_exists('generateVoucherNo')) {

        function generateVoucherNo(string $voucherCode): string
        {
            $fy = currentFiscalYear();

            if (! $fy) {
                throw new Exception('No active fiscal year found.');
            }

            $year = date('y', strtotime($fy->start_date)); // e.g. 24

            // Count existing vouchers for this type + fiscal year
            $lastNumber = JournalEntry::where('voucher_type_id', VoucherType::idByCode($voucherCode))
                ->where('fiscal_year_id', $fy->id)
                ->count();

            $next = $lastNumber + 1;

            return sprintf(
                '%s-%s-%06d',
                $voucherCode,
                $year,
                $next
            );
        }
    }

    if (! function_exists('stringShortner')) {
        function stringShortner($name, $length)
        {
            if (empty($name)) {
                return '';
            }
            if (mb_strlen($name) > $length) {
                return mb_substr($name, 0, $length) . '...';
            }

            return $name;
        }
    }

    if (! function_exists('generateInvoiceNo')) {      
        function generateInvoiceNo(
            string $prefix,
            ?int $branchId = null,
            string $table = 'expenses',
            string $column = 'invoice_no'
        ): string {

            $branchId = $branchId ?? current_branch_id();
            $date     = Carbon::now()->format('Ymd');

            $base = "{$prefix}-{$date}-";

            // 🔐 lock table row to avoid race condition
            $last = DB::table($table)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereDate('created_at', Carbon::today())
                ->where($column, 'like', "{$base}%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value($column);

            $next = 1;

            if ($last) {
                $parts = explode('-', $last);
                $next  = (int) end($parts) + 1;
            }

            return $base . str_pad($next, 5, '0', STR_PAD_LEFT);
        }
    }

    if (! function_exists('isProductUnderAnyOffer')) {
        function isProductUnderAnyOffer($productId)
        {
            $allOffer = Offer::where('is_active', 1)->where('start_date', '<=', now())->where('end_date', '>=', now())->get();


            foreach ($allOffer as $offer) {
                if ($offer->offer_type == 'gift') {
                    $eligibleOffer = OfferGift::where('offer_id', $offer->id)->where('buy_product_id', $productId)->first();
                    if ($eligibleOffer) {
                        return $offer;
                    }
                } else if ($offer->offer_type == 'bundle') {
                    $eligibleOffer = OfferGift::where('offer_id', $offer->id)->where('buy_product_id', $productId)->first();
                    if ($eligibleOffer) {
                        return $offer;
                    }
                } else if ($offer->offer_type == 'shipping') {
                    $eligibleOffer = OfferShipping::where('offer_id', $offer->id)->where('product_id', $productId)->first();
                    if ($eligibleOffer) {
                        return $offer;
                    }
                } else if ($offer->offer_type == 'discount') {
                    $offer_discount = OfferDiscount::where('offer_id', $offer->id)->first();
                    $eligibleOffer = OfferDiscountProduct::where('product_id', $productId)->where('offer_discount_id', $offer_discount->id)->first();

                    if ($eligibleOffer) {
                        return $offer;
                    }
                }
            }
            return null;
        }
    }

    if (! function_exists('applyDiscount')) {
        function applyDiscount($discountRule, $product)
        {
            // $discountRule = OfferDiscount::where('offer_id', $offer->id)->first();

            if ($discountRule->discount_type == 'percentage') {
                return $product->mrp * ($discountRule->discount_value / 100);
            } else {
                return $discountRule->discount_value;
            }
        }
    }

    if (! function_exists('discountPercentage')) {
        function discountPercentage($mrp, $offerPrice)
        {
            if ($mrp > 0) {
                $discount = $mrp - $offerPrice;
                return ($discount / $mrp) * 100;
            }
            return 0;
        }
    }

        // Location Hierarchy
        if (! function_exists('getParentLocations')) {
       
        function getParentLocations($upazilaId)
        {
            $upazila = Upazila::with('upazila_district.district_division')->find($upazilaId);

            if (!$upazila) {
                return null;
            }

            $district = $upazila->upazila_district;
            $division = $district ? $district->district_division : null;

            return [
                'upazila_id'   => $upazila->id,
                'district_id'  => $district ? $district->district_id : null,
                'division_id'  => $division ? $division->id : null,
            ];
        }

        }

        // Location Hierarchy for Multiple Upazilas
        if (! function_exists('getParentLocationsMultiple')) {
            function getParentLocationsMultiple($upazilaIds)
            {
                if (empty($upazilaIds)) {
                    return [
                        'territories'    => [],
                        'upazila_ids'    => [],
                        'district_ids'   => [],
                        'district_names' => [],
                        'division_ids'   => [],
                        'division_names' => [],
                    ];
                }

                if (!is_array($upazilaIds)) {
                    $upazilaIds = [$upazilaIds];
                }

                $upazilaIds = array_values(array_filter(array_map('intval', $upazilaIds)));

                if (empty($upazilaIds)) {
                    return [
                        'territories'    => [],
                        'upazila_ids'    => [],
                        'district_ids'   => [],
                        'district_names' => [],
                        'division_ids'   => [],
                        'division_names' => [],
                    ];
                }

                $upazilas = Upazila::with('upazila_district.district_division')
                    ->whereIn('id', $upazilaIds)
                    ->get();

                $territories   = [];
                $districtIds   = [];
                $districtNames = [];
                $divisionIds   = [];
                $divisionNames = [];

                foreach ($upazilas as $upazila) {
                    $district = $upazila->upazila_district;
                    $division = $district ? $district->district_division : null;

                    $districtId = $district ? $district->district_id : null;
                    $divisionId = $division ? $division->id : null;

                    $territories[] = [
                        'upazila_id'    => $upazila->id,
                        'upazila_name'  => $upazila->upazila_name,
                        'district_id'   => $districtId,
                        'district_name' => $district ? $district->district_name : null,
                        'division_id'   => $divisionId,
                        'division_name' => $division ? $division->name : null,
                    ];

                    if ($districtId && !in_array($districtId, $districtIds)) {
                        $districtIds[]   = $districtId;
                        $districtNames[] = $district->district_name;
                    }

                    if ($divisionId && !in_array($divisionId, $divisionIds)) {
                        $divisionIds[]   = $divisionId;
                        $divisionNames[] = $division->name;
                    }
                }

                return [
                    'territories'    => $territories,
                    'upazila_ids'    => $upazilas->pluck('id')->all(),
                    'district_ids'   => $districtIds,
                    'district_names' => $districtNames,
                    'division_ids'   => $divisionIds,
                    'division_names' => $divisionNames,
                ];
            }
        }

}

if (!function_exists('clean_phone_number')) {
    /**
     * Clean and normalize a Bangladeshi phone number.
     * Handles multiple numbers (takes first), converts Bangla digits (০-৯) to English (0-9),
     * strips +88 / 88 / 0088, removes spaces/dashes, and prepends missing leading 0 (e.g. 17XXXXXXXX -> 017XXXXXXXX).
     *
     * @param string|null $phone
     * @return string
     */
    function clean_phone_number(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        // If multiple numbers separated by slash, comma, semicolon, newline or pipe, take the first number
        $parts = preg_split('/[\/;,|\n]/', (string) $phone);
        $phone = trim($parts[0] ?? '');
        if ($phone === '') {
            return '';
        }

        // Convert Bangla numbers (০-৯) to English digits (0-9)
        $banglaDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $phone = str_replace($banglaDigits, $englishDigits, $phone);

        // Remove all non-numeric characters (hyphens, spaces, dots, etc.)
        $clean = preg_replace('/[^0-9]/', '', $phone);

        // If starts with 00880, strip 0088
        if (str_starts_with($clean, '00880')) {
            $clean = substr($clean, 4);
        }
        // If starts with 880, strip 88
        elseif (str_starts_with($clean, '880')) {
            $clean = substr($clean, 2);
        }

        // If 10 digits starting with '1' (Excel automatically strips leading 0 from numbers: e.g. 17XXXXXXXX -> 017XXXXXXXX)
        if (strlen($clean) === 10 && str_starts_with($clean, '1')) {
            $clean = '0' . $clean;
        }

        return $clean;
    }
}

if (!function_exists('get_bd_district_bangla_map')) {
    /**
     * Complete mapping of all 64 Bangladesh districts (Bangla variations -> English district_name in DB)
     */
    function get_bd_district_bangla_map(): array
    {
        return [
            // Rangpur Division (id: 7)
            'কুড়িগ্রাম'       => 'Kurigram',
            'কুড়িগ্রাম'       => 'Kurigram',
            'দিনাজপুর'        => 'Dinajpur',
            'গাইবান্ধা'       => 'Gaibandha',
            'লালমনিরহাট'      => 'Lalmonirhat',
            'নীলফামারী'       => 'Nilphamari',
            'নীলফামারি'       => 'Nilphamari',
            'পঞ্চগড়'          => 'Panchagarh',
            'পঞ্চগড়'          => 'Panchagarh',
            'রংপুর'           => 'Rangpur',
            'ঠাকুরগাঁও'       => 'Thakurgaon',
            'ঠাকুরগাও'       => 'Thakurgaon',

            // Mymensingh Division (id: 8)
            'জামালপুর'        => 'Jamalpur',
            'ময়মনসিংহ'        => 'Mymensingh',
            'ময়মনসিং'         => 'Mymensingh',
            'নেত্রকোণা'       => 'Netrokona',
            'নেত্রকোনা'       => 'Netrokona',
            'শেরপুর'          => 'Sherpur',

            // Rajshahi Division (id: 2)
            'রাজশাহী'         => 'Rajshahi',
            'বগুড়া'           => 'Bogra',
            'বগুড়া'           => 'Bogra',
            'বগুরা'           => 'Bogra',
            'জয়পুরহাট'        => 'Joypurhat',
            'জয়পুরহাঁট'       => 'Joypurhat',
            'নওগাঁ'           => 'Naogaon',
            'নওগা'            => 'Naogaon',
            'নাটোর'           => 'Natore',
            'নাবাবগঞ্জ'       => 'Chapainawabganj',
            'চাঁপাইনবাবগঞ্জ'   => 'Chapainawabganj',
            'চাপাইনবাবগঞ্জ'   => 'Chapainawabganj',
            'পাবনা'           => 'Pabna',
            'সিরাজগঞ্জ'       => 'Sirajganj',

            // Khulna Division (id: 3)
            'কুষ্টিয়া'        => 'Kushtia',
            'কুষ্টিয়া'        => 'Kushtia',
            'খুলনা'           => 'Khulna',
            'বাগেরহাট'        => 'Bagerhat',
            'চুয়াডাঙ্গা'       => 'Chuadanga',
            'চুয়াডাঙ্গা'       => 'Chuadanga',
            'যশোর'            => 'Jessore',
            'ঝিনাইদহ'         => 'Jhenaidah',
            'মাগুরা'          => 'Magura',
            'মেহেরপুর'        => 'Meherpur',
            'নড়াইল'           => 'Narail',
            'নড়াইল'           => 'Narail',
            'সাতক্ষীরা'       => 'Satkhira',
            'সাতক্ষিরা'       => 'Satkhira',

            // Dhaka Division (id: 6)
            'ঢাকা'            => 'Dhaka',
            'ঢাকা মহানগর'     => 'Dhaka Mahanagari',
            'ফরিদপুর'         => 'Faridpur',
            'গাজীপুর'         => 'Gazipur',
            'গোপালগঞ্জ'       => 'Gopalganj',
            'কিশোরগঞ্জ'       => 'Kishoreganj',
            'মাদারীপুর'       => 'Madaripur',
            'মাদারিপুর'       => 'Madaripur',
            'মানিকগঞ্জ'       => 'Manikganj',
            'মুন্সীগঞ্জ'       => 'Munshiganj',
            'মুন্সিগঞ্জ'       => 'Munshiganj',
            'নারায়ণগঞ্জ'      => 'Narayanganj',
            'নারায়নগঞ্জ'      => 'Narayanganj',
            'নরসিংদী'         => 'Narsingdi',
            'নরসিংদি'         => 'Narsingdi',
            'রাজবাড়ী'         => 'Rajbari',
            'রাজবাড়ি'         => 'Rajbari',
            'শরীয়তপুর'        => 'Shariatpur',
            'শরিয়তপুর'        => 'Shariatpur',
            'টাঙ্গাইল'        => 'Tangail',

            // Barisal Division (id: 4)
            'বরিশাল'          => 'Barisal',
            'বরগুনা'          => 'Barguna',
            'ভোলা'            => 'Bhola',
            'ঝালকাঠি'         => 'Jhalakati',
            'ঝালকাঠী'         => 'Jhalakati',
            'পটুয়াখালী'       => 'Patuakhali',
            'পটুয়াখালি'       => 'Patuakhali',
            'পিরোজপুর'        => 'Pirojpur',

            // Chattogram Division (id: 1)
            'চট্টগ্রাম'       => 'Chattogram',
            'কক্সবাজার'       => "Cox's Bazar",
            'বান্দরবান'       => 'Bandarban',
            'ব্রাহ্মণবাড়িয়া'   => 'Brahmanbaria',
            'ব্রাহ্মণবাড়িয়া'   => 'Brahmanbaria',
            'বি-বাড়িয়া'       => 'Brahmanbaria',
            'চাঁদপুর'         => 'Chandpur',
            'কুমিল্লা'        => 'Comilla',
            'ফেনী'            => 'Feni',
            'ফেনি'            => 'Feni',
            'খাগড়াছড়ি'       => 'Khagrachhari',
            'খাগড়াছড়ি'       => 'Khagrachhari',
            'লক্ষ্মীপুর'       => 'Lakshmipur',
            'লক্ষীপুর'        => 'Lakshmipur',
            'নোয়াখালী'        => 'Noakhali',
            'নোয়াখালি'        => 'Noakhali',
            'রাঙ্গামাটি'      => 'Rangamati',
            'রাঙামাটি'        => 'Rangamati',

            // Sylhet Division (id: 5)
            'সিলেট'           => 'Sylhet',
            'হবিগঞ্জ'         => 'Habiganj',
            'মৌলভীবাজার'      => 'Moulvibazar',
            'সুনামগঞ্জ'       => 'Sunamganj',
        ];
    }
}

if (!function_exists('bangla_to_english_phonetic')) {
    /**
     * Transliterates a Bangla upazila name to English for matching against English upazila_name.
     */
    function bangla_to_english_phonetic(?string $bangla): string
    {
        if (empty($bangla)) {
            return '';
        }

        $s = trim((string) $bangla);
        $s = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $s);
        $s = preg_replace('/\s*(জেলা|উপজেলা|থানা|district|upazila|upazilla|thana)\b/ui', '', $s);
        $s = trim($s);

        // Normalize decomposed nukta: ড+় -> ড়, ঢ+় -> ঢ়, য+় -> য়
        $s = str_replace(
            ["\xE0\xA6\xA1\xE0\xA6\xBC", "\xE0\xA6\xA2\xE0\xA6\xBC", "\xE0\xA6\xAF\xE0\xA6\xBC"],
            ['ড়', 'ঢ়', 'য়'],
            $s
        );

        $customMap = [
            'সদর'           => 'sadar',
            'রৌমারি'        => 'rowmari',
            'রৌমারী'        => 'rowmari',
            'রাজিবপুর'      => 'rajibpur',
            'চর রাজিবপুর'   => 'char rajibpur',
            'চররাজিবপুর'    => 'char rajibpur',
            'চিলমারী'       => 'chilmari',
            'চিলমারি'       => 'chilmari',
            'উলিপুর'        => 'ulipur',
            'নাগেশ্বরী'     => 'nageshwari',
            'নাগেশ্বরি'     => 'nageshwari',
            'ভুরুঙ্গামারী'   => 'bhurungamari',
            'ভুরুঙ্গামারি'   => 'bhurungamari',
            'ভূরুঙ্গামারী'   => 'bhurungamari',
            'ফুলবাড়ী'       => 'phulbari',
            'ফুলবাড়ি'       => 'phulbari',
            'ফুলবাড়ি'       => 'phulbari',
            'রাজারহাট'      => 'rajarhat',
            'গাংনী'         => 'gangni',
            'মুজিবনগর'      => 'mujibnagar',
            'মিরপুর'        => 'mirpur',
            'কুমারখালী'     => 'kumarkhali',
            'খোকসা'         => 'khoksa',
            'ভেড়ামারা'      => 'bheramara',
            'দৌলতপুর'      => 'daulatpur',
            'বোয়ালিয়া'      => 'boalia',
            'মতিহার'        => 'matihar',
            'রাজপাড়া'      => 'rajpara',
            'পবা'           => 'paba',
            'বাঘা'          => 'bagha',
            'বাঘমারা'       => 'bagmara',
            'চারঘাট'        => 'charghat',
            'দুর্গাপুর'      => 'durgapur',
            'গোদাগাড়ী'      => 'godagari',
            'মোহনপুর'       => 'mohanpur',
            'পুঠিয়া'        => 'puthia',
            'তানোর'         => 'tanore',
        ];

        $cleanBangla = mb_strtolower($s);
        if (isset($customMap[$cleanBangla])) {
            return $customMap[$cleanBangla];
        }

        $charMap = [
            'ক' => 'k', 'খ' => 'kh', 'গ' => 'g', 'ঘ' => 'gh', 'ঙ' => 'ng',
            'চ' => 'ch', 'ছ' => 'chh', 'জ' => 'j', 'ঝ' => 'jh', 'ঞ' => 'n',
            'ট' => 't', 'ঠ' => 'th', 'ড' => 'd', 'ঢ' => 'dh', 'ণ' => 'n',
            'ত' => 't', 'থ' => 'th', 'দ' => 'd', 'ধ' => 'dh', 'ন' => 'n',
            'প' => 'p', 'ফ' => 'ph', 'ব' => 'b', 'ভ' => 'bh', 'ম' => 'm',
            'য' => 'j', 'র' => 'r', 'ল' => 'l', 'শ' => 'sh', 'ষ' => 'sh', 'স' => 's', 'হ' => 'h',
            'ড়' => 'r', 'ঢ়' => 'rh', 'য়' => 'y', 'ৎ' => 't', 'ং' => 'ng', 'ঃ' => 'h', 'ঁ' => '',
            'অ' => 'a', 'আ' => 'a', 'ই' => 'i', 'ঈ' => 'i', 'উ' => 'u', 'ঊ' => 'u',
            'ঋ' => 'ri', 'এ' => 'e', 'ঐ' => 'oi', 'ও' => 'o', 'ঔ' => 'ou',
            'া' => 'a', 'ি' => 'i', 'ী' => 'i', 'ু' => 'u', 'ূ' => 'u', 'ৃ' => 'ri',
            'ে' => 'e', 'ৈ' => 'oi', 'ো' => 'o', 'ৌ' => 'ou', '্' => '',
        ];

        $out = '';
        $len = mb_strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($s, $i, 1);
            $out .= $charMap[$ch] ?? $ch;
        }

        return mb_strtolower(preg_replace('/[\s\-_]+/u', '', $out));
    }
}

if (!function_exists('bd_canonical_term')) {
    /**
     * Normalize a Bangladeshi geo term (district, upazila, division).
     * Strips zero-width characters, BOM, common suffixes (জেলা, উপজেলা, থানা),
     * and normalizes Unicode nukta characters.
     */
    function bd_canonical_term(?string $term): string
    {
        if (empty($term)) {
            return '';
        }

        $s = trim((string) $term);
        $s = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $s);
        $s = preg_replace('/\s*(জেলা|উপজেলা|থানা|district|dist|upazila|upazilla|thana)\b/ui', '', $s);
        $s = trim($s);

        // Normalize decomposed nukta: ড+় -> ড়, ঢ+় -> ঢ়, য+় -> য়
        $s = str_replace(
            ["\xE0\xA6\xA1\xE0\xA6\xBC", "\xE0\xA6\xA2\xE0\xA6\xBC", "\xE0\xA6\xAF\xE0\xA6\xBC"],
            ['ড়', 'ঢ়', 'য়'],
            $s
        );

        return mb_strtolower($s);
    }
}

if (!function_exists('resolve_district_by_name')) {
    /**
     * Resolve a District model by its English or Bangla name across all districts in BD.
     * Uses in-memory static cache for lightning performance during batch imports.
     *
     * @param string|null $name
     * @return \App\Models\backend\District|null
     */
    function resolve_district_by_name(?string $name): ?\App\Models\backend\District
    {
        if (empty($name)) {
            return null;
        }

        $canonical = bd_canonical_term($name);
        if ($canonical === '') {
            return null;
        }

        static $districtsCache = null;
        if ($districtsCache === null) {
            $districtsCache = \App\Models\backend\District::with('district_division')->get();
        }

        // 1. Direct Bangla Map (handles all 64 districts in Bangla)
        $banglaMap = get_bd_district_bangla_map();
        if (isset($banglaMap[$canonical])) {
            $targetEnglish = mb_strtolower($banglaMap[$canonical]);
            foreach ($districtsCache as $d) {
                if (mb_strtolower((string)$d->district_name) === $targetEnglish) {
                    return $d;
                }
            }
        }

        // 2. Exact match in English (or if DB has Bangla in district_bn_name)
        foreach ($districtsCache as $d) {
            $dName = mb_strtolower(trim((string)$d->district_name));
            $dBnName = mb_strtolower(trim((string)$d->district_bn_name));

            if (($dName !== '' && $dName === $canonical) || ($dBnName !== '' && $dBnName === $canonical)) {
                return $d;
            }
        }

        // 3. Common English spelling aliases
        $aliases = [
            'chittagong'       => 'chattogram',
            'bogra'            => 'bogra',
            'bogura'           => 'bogra',
            'comilla'          => 'comilla',
            'cumilla'          => 'comilla',
            'barisal'          => 'barisal',
            'barishal'         => 'barisal',
            'jessore'          => 'jessore',
            'jashore'          => 'jessore',
            'kustia'           => 'kushtia',
            'kushtia'          => 'kushtia',
            'kurigram'         => 'kurigram',
            'nawabganj'        => 'chapainawabganj',
            'chapainawabgonj'  => 'chapainawabganj',
            'sirajgonj'        => 'sirajganj',
            'sunamgonj'        => 'sunamganj',
            'habigonj'         => 'habiganj',
            'moulvibazar'      => 'moulvibazar',
            'moulovibazar'     => 'moulvibazar',
            'b.baria'          => 'brahmanbaria',
            'b-baria'          => 'brahmanbaria',
            'bbaria'           => 'brahmanbaria',
            'laxmipur'         => 'lakshmipur',
            'jhalokati'        => 'jhalakati',
            'jhalakathi'       => 'jhalakati',
            'coxsbazar'        => "cox's bazar",
            'coxbazar'         => "cox's bazar",
            'cox bazar'        => "cox's bazar",
            'mymensing'        => 'mymensingh',
            'netrakona'        => 'netrokona',
            'panchagar'        => 'panchagarh',
            'gaibanda'         => 'gaibandha',
            'jaipurhat'        => 'joypurhat',
            'munshigonj'       => 'munshiganj',
            'manikgonj'        => 'manikganj',
            'narayangonj'      => 'narayanganj',
            'kishoregonj'      => 'kishoreganj',
            'gopalgonj'        => 'gopalganj',
        ];

        if (isset($aliases[$canonical])) {
            $target = $aliases[$canonical];
            foreach ($districtsCache as $d) {
                if (mb_strtolower((string)$d->district_name) === $target) {
                    return $d;
                }
            }
        }

        // 4. Safe Partial match (STRICT: length >= 3 and NEVER with empty string)
        if (mb_strlen($canonical) >= 3) {
            foreach ($districtsCache as $d) {
                $dName = mb_strtolower(trim((string)$d->district_name));
                if ($dName !== '' && (str_contains($dName, $canonical) || str_contains($canonical, $dName))) {
                    return $d;
                }
            }
        }

        return null;
    }
}

if (!function_exists('resolve_upazila_by_name')) {
    /**
     * Resolve an Upazila model by its name, optionally scoped by district ID.
     * Uses in-memory district-scoped caching for high speed across hundreds of rows.
     *
     * @param string|null $name
     * @param int|null $districtId
     * @return \App\Models\backend\Upazila|null
     */
    function resolve_upazila_by_name(?string $name, ?int $districtId = null): ?\App\Models\backend\Upazila
    {
        if (empty($name)) {
            return null;
        }

        $canonical = bd_canonical_term($name);
        if ($canonical === '') {
            return null;
        }

        static $upazilasByDistrict = [];
        if ($districtId && !isset($upazilasByDistrict[$districtId])) {
            $upazilasByDistrict[$districtId] = \App\Models\backend\Upazila::with('upazila_district.district_division')
                ->where('upazila_district_id', $districtId)
                ->get();
        }

        $candidates = $districtId ? ($upazilasByDistrict[$districtId] ?? collect()) : null;

        if ($candidates && $candidates->isNotEmpty()) {
            // 1. Special Sadar match (e.g. user wrote "সদর" or "Sadar")
            if (in_array($canonical, ['সদর', 'sadar'])) {
                foreach ($candidates as $u) {
                    $uName = mb_strtolower((string)$u->upazila_name);
                    if (str_contains($uName, 'sadar')) {
                        return $u;
                    }
                }
            }

            // 2. Exact English match (if Excel had English)
            foreach ($candidates as $u) {
                $uName = mb_strtolower(trim((string)$u->upazila_name));
                $uBnName = mb_strtolower(trim((string)$u->upazila_bn_name));
                if (($uName !== '' && $uName === $canonical) || ($uBnName !== '' && $uBnName === $canonical)) {
                    return $u;
                }
            }

            // 3. Phonetic Bangla transliteration match (e.g. রৌমারি -> rowmari -> Rowmari)
            $phonetic = bangla_to_english_phonetic($name);
            if ($phonetic !== '') {
                foreach ($candidates as $u) {
                    $uNameClean = mb_strtolower(preg_replace('/[\s\-_]+/u', '', (string)$u->upazila_name));
                    if ($uNameClean === $phonetic || str_contains($uNameClean, $phonetic) || str_contains($phonetic, $uNameClean)) {
                        return $u;
                    }
                }
            }

            // 4. Safe partial match under this district (STRICT: length >= 3)
            if (mb_strlen($canonical) >= 3) {
                foreach ($candidates as $u) {
                    $uName = mb_strtolower(trim((string)$u->upazila_name));
                    if ($uName !== '' && (str_contains($uName, $canonical) || str_contains($canonical, $uName))) {
                        return $u;
                    }
                }
            }
        }

        // Global fallback across all upazilas (only if clean term has at least 3 characters!)
        if (mb_strlen($canonical) >= 3) {
            $query = \App\Models\backend\Upazila::with('upazila_district.district_division');
            if ($districtId) {
                $query->where('upazila_district_id', $districtId);
            }

            // Phonetic match across all upazilas
            $phonetic = bangla_to_english_phonetic($name);
            if ($phonetic !== '') {
                $u = (clone $query)->whereRaw("LOWER(REPLACE(REPLACE(upazila_name, ' ', ''), '-', '')) LIKE ?", ["%{$phonetic}%"])->first();
                if ($u) {
                    return $u;
                }
            }

            // Try exact match in English
            $upazila = (clone $query)->where(function ($q) use ($canonical) {
                $q->where('upazila_name', $canonical);
            })->first();

            if ($upazila) {
                return $upazila;
            }

            // Try LIKE match in English
            return (clone $query)->where(function ($q) use ($canonical) {
                $q->where('upazila_name', 'like', "%{$canonical}%");
            })->first();
        }

        return null;
    }
}

//unique_product_code
if (! function_exists('generate_product_code')) {
   
    function generate_product_code($customPrefix = null)
    {
        $prefix      = $customPrefix ?? 'PRD-'; 
        $startNumber = 1;                       
        $padLength   = 3;                    

        try {
            $prefixLen = strlen($prefix);

            if ($prefixLen > 0) {
                $maxNum = DB::table('products')
                    ->where('product_code', 'LIKE', $prefix . '%')
                    ->selectRaw("MAX(CAST(SUBSTRING(product_code, ?) AS UNSIGNED)) as max_no", [$prefixLen + 1])
                    ->value('max_no');

                $nextNumber = $maxNum !== null ? ((int) $maxNum + 1) : $startNumber;
                $code = $prefix . str_pad($nextNumber, $padLength, '0', STR_PAD_LEFT);
            } else {
                $maxNum = DB::table('products')
                    ->whereNotNull('product_code')
                    ->where('product_code', '!=', '')
                    ->whereRaw("product_code REGEXP '^[0-9]+$'")
                    ->selectRaw("MAX(CAST(product_code AS UNSIGNED)) as max_no")
                    ->value('max_no');

                if ($maxNum !== null) {
                    $nextNumber = (int) $maxNum + 1;
                } else {
                    $maxId = DB::table('products')->max('id') ?? 0;
                    $nextNumber = max($startNumber, $maxId + 1);
                }

                $code = (string) $nextNumber;
            }

            
            while (DB::table('products')->where('product_code', $code)->exists()) {
                $nextNumber++;
                $code = ($prefixLen > 0)
                    ? $prefix . str_pad($nextNumber, $padLength, '0', STR_PAD_LEFT)
                    : (string) $nextNumber;
            }

            return $code;

        } catch (\Throwable $e) {
            
            $fallbackId = (DB::table('products')->max('id') ?? 0) + 1;
            return (!empty($prefix) ? $prefix : '') . str_pad($fallbackId, $padLength, '0', STR_PAD_LEFT);
        }
    }
}


