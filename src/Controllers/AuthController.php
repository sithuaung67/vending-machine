<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\User;

final class AuthController
{
    public function showLogin(Request $request): void
    {
        if (Auth::check()) Response::redirect('/products');
        Response::html(view('auth/login'));
    }

    public function login(Request $request): void
    {
        if (!verify_csrf($request->input('_csrf'))) Response::redirect('/login');

        $v = Validator::make($request->body, [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if ($v->fails()) {
            set_old(['email' => $request->input('email')]);
            flash('error', 'Please enter a valid email and password.');
            Response::redirect('/login');
        }

        if (!Auth::attempt((string) $request->input('email'), (string) $request->input('password'))) {
            set_old(['email' => $request->input('email')]);
            flash('error', 'Invalid credentials.');
            Response::redirect('/login');
        }
        clear_old();
        Response::redirect('/products');
    }

    public function showRegister(Request $request): void
    {
        if (Auth::check()) Response::redirect('/products');
        Response::html(view('auth/register'));
    }

    public function register(Request $request): void
    {
        if (!verify_csrf($request->input('_csrf'))) Response::redirect('/register');

        $v = Validator::make($request->body, [
            'name' => 'required|min_len:2',
            'email' => 'required|email',
            'password' => 'required|min_len:6',
        ]);
        $users = new User();
        if ($v->passes() && $users->emailExists((string) $request->input('email'))) {
            flash('error', 'That email is already registered.');
            $v = Validator::make([], ['email' => 'required']); // force fail path below
        }
        if ($v->fails()) {
            set_old(['name' => $request->input('name'), 'email' => $request->input('email')]);
            flash('error', flash('error') ?? 'Please fix the errors and try again.');
            Response::redirect('/register');
        }

        $id = $users->create(
            (string) $request->input('name'),
            (string) $request->input('email'),
            (string) $request->input('password'),
            'user'
        );
        Auth::login($users->find($id));
        clear_old();
        Response::redirect('/products');
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        Response::redirect('/login');
    }
}
