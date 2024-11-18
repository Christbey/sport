<?php

namespace App\Http\Controllers;

use App\Models\AccessRequest;
use App\Models\User;
use App\Notifications\NewAccessRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Laravel\Jetstream\Mail\TeamInvitation;

class AccessRequestController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $accessRequest = AccessRequest::create([
            'name' => $request->name,
            'email' => $request->email,
            'reason' => $request->reason,
        ]);

        // Notify admin
        $admin = User::where('email', 'Josh@picksports.app')->first();
        $admin->notify(new NewAccessRequest($accessRequest));

        return redirect()->route('register')
            ->with('status', 'Your access request has been submitted. You will receive an email invitation if approved.');
    }

    public function index()
    {
        $requests = AccessRequest::orderBy('created_at', 'desc')->get();
        return view('admin.access-requests.index', compact('requests'));
    }

    public function approve(AccessRequest $accessRequest)
    {
        $team = auth()->user()->currentTeam;

        // Create team invitation
        $invitation = $team->teamInvitations()->create([
            'email' => $accessRequest->email,
            'role' => 'viewer'
        ]);

        // Send the invitation email
        Mail::to($invitation->email)->send(new TeamInvitation($invitation));

        $accessRequest->update(['status' => 'approved']);

        return back()->with('success', 'Access request approved and invitation sent.');
    }

    public function deny(AccessRequest $accessRequest)
    {
        $accessRequest->update(['status' => 'denied']);
        return back()->with('success', 'Access request denied.');
    }

}