<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\MassDestroyUserRequest;
use Symfony\Component\HttpFoundation\Response;

class UsersController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('user_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = User::where('id', '!=', 1)->with(['roles'])->get();

        return view('admin.users.index', compact('data'));
    }

    public function create()
    {
        abort_if(Gate::denies('user_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $roles = Role::pluck('title', 'id');

        return view('admin.users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request)
    {
        $role = Role::where('title', $request->input('role'))->firstOrFail();

        $user = User::create([
            'name' => $request->input('name'),
            'username' => $request->input('username'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
        ]);

        $user->roles()->sync([$role->id]);

        return redirect()->route('admin.users.index');
    }


    public function edit(User $user)
    {
        abort_if(Gate::denies('user_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $roles = Role::pluck('title', 'id');

        $userRole = $user->roles->first()->title ?? null;

        $user->load('roles');

        return view('admin.users.edit', compact('roles', 'user', 'userRole'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $role = Role::where('title', $request->input('role'))->firstOrFail();

        $user->update([
            'name' => $request->input('name'),
            'username' => $request->input('username'),
            'password' => bcrypt($request->input('password')),
        ]);

        $user->roles()->sync([$role->id]);

        return redirect()->route('admin.users.index');
    }

    public function destroy(User $user)
    {
        abort_if(Gate::denies('user_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user->delete();

        return back();
    }

    public function massDestroy(MassDestroyUserRequest $request)
    {
        $users = User::find(request('ids'));

        foreach ($users as $user) {
            $user->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
