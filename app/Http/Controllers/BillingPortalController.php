<?php
// app/Http/Controllers/BillingPortalController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BillingPortalController extends Controller
{
    /**
     * Redirect to the Stripe Billing Portal.
     */
    public function redirectToPortal(Request $request)
    {
        return $request->user()
            ->redirectToBillingPortal(route('dashboard'));
    }
}