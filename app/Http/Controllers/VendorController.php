<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\VendorRegisterNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

class VendorController extends Controller
{
    public function VendorDashboard()
    {
        return view('vendor.index');
    } //End Method

    public  function VendorLogin()
    {
        return view('vendor.vendor_login');
    }  //End Method

    public  function VendorLogout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/vendor/login');
    }  //End Method

    public function VendorProfile()
    {
        $id = Auth::user()->id;
        $vendorData = User::findOrFail($id);

        return view('vendor.vendor_profile_view', compact('vendorData'));
    } //End Method

    public function VendorProfileStore(Request $request)
    {
        $id = Auth::user()->id;
        $data = User::findOrFail($id);
        $data->name = $request->name;
        $data->email = $request->email;
        $data->phone = $request->phone;
        $data->address = $request->address;
        $data->vendor_join = $request->vendor_join;
        $data->vendor_short_info = $request->vendor_short_info;

        if ($request->file('photo')) {
            $file = $request->file('photo');
            @unlink(public_path('upload/vendor_images/' . $data->photo));
            $filename = date('YmdHi') . $file->getClientOriginalName();
            $file->move(public_path('upload/vendor_images'), $filename);
            $data['photo'] = $filename;
        }
        $data->save();

        $notification =  array(
            'message' => 'Vendor profile Updated Successfully',
            'alert-type' => 'success',
        );
        return redirect()->back()->with($notification);
    } //End Method

    public function VendorChangePassword()
    {
        return view('vendor.vendor_change_password');
    } //End Method

    public function VendorUpdatePassword(Request $request)
    {
        //validation
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|confirmed',
        ]);
        // Mach the old password
        if (!Hash::check($request->old_password, auth::user()->password)) {
            return back()->with("error", "Old password Doesn't Match!!");
        }

        //update the new password
        User::whereId(auth()->user()->id)->update([
            'password' => Hash::make($request->new_password)
        ]);
        return back()->with('status', 'Password changed successfully');
    } //End Method

    public function BecomeVendor()
    {
        return view('auth.become_vendor');
    } //End Method


    public function VendorRegister(Request $request)
    {

        $vuser = User::where('role', 'admin')->get();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed'],
        ]);

        $user = User::insert([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'vendor_join' => $request->vendor_join,
            'password' => Hash::make($request->password),
            'role' => 'vendor',
            'status' => 'inactive',
        ]);

        $notification = array(
            'message' => 'Vendor Registered Successfully',
            'alert-type' => 'success'
        );

        Notification::send($vuser, new VendorRegisterNotification($request));

        return redirect()->route('vendor.login')->with($notification);
    } //End Method

}
