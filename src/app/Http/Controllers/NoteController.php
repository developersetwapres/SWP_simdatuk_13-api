<?php

namespace App\Http\Controllers;

use App\Http\Requests\Note\UpdateNoteByUserIdRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class NoteController extends Controller
{
    public function __construct(protected Request $request) {}

    public function show(): JsonResponse
    {
        $notes = DB::table('user_notes as un');
        $notes->leftJoin('users as u1', 'u1.id', '=', 'un.user_id');
        $notes->leftJoin('users as u2', 'u2.id', '=', 'un.giver_id');
        $notes->select('un.id', 'u2.id as giver_id', 'u2.name as giver_name', 'un.description', 'un.created_at');
        $notes->where('u1.id', $this->request->userid);

        return $this->response(200, 'success', $notes->get());
    }

    public function update(UpdateNoteByUserIdRequest $request): JsonResponse
    {
        $userNotes = DB::table('user_notes')->where('user_id', $this->request->userid)->select('id')->get();

        if (is_null($this->request->notes)) {
            DB::table('user_notes')->where('user_id', $this->request->userid)->delete();

            return $this->response(200, 'Catatan berhasil diupdate.');
        }

        $deletedIds = array_diff(Arr::pluck($userNotes, 'id'), Arr::pluck($this->request->notes, 'id'));
        DB::table('user_notes')->whereIn('id', $deletedIds)->delete();
        $notes = [];

        foreach ($this->request->notes as $item) {
            $item['giver_id'] = $this->request->user()->id;
            $item['user_id'] = $this->request->userid;

            if (is_null($item['id'])) {
                unset($item['id']);
                $notes[] = $item;
            } else {
                DB::table('user_notes')->where('id', $item['id'])->updateTs($item);
            }
        }

        if (count($notes) > 0) {
            DB::table('user_notes')->insertTs($notes);
        }

        return $this->response(200, 'Catatan berhasil diupdate.');
    }
}
