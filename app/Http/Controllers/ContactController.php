<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        $tags = Tag::all();

        return view('contact.index', compact('categories', 'tags'));
    }

    public function confirm(StoreContactRequest $request)
    {
        $validated = $request->validated();

        $category = Category::findOrFail(
            $validated['category_id']
        );

        $tags = Tag::whereIn(
            'id',
            $validated['tag_ids'] ?? []
        )->get();

        return view('contact.confirm', compact('validated', 'category', 'tags'));
    }

    public function store(StoreContactRequest $request)
    {
        if ($request->input('action') === 'back') {
            return redirect()
                ->route('contact.index')
                ->withInput();
        }

        $validated = $request->validated();

        $contactData = $validated;
        unset($contactData['tag_ids']);

        $contact = Contact::create($contactData);

        if (! empty($validated['tag_ids'])) {
            $contact->tags()->attach($validated['tag_ids']);
        }

        return redirect()->route('contact.thanks');
    }

    public function export(ExportContactRequest $request): StreamedResponse
    {
        $conditions = $request->validated();

        $contacts = Contact::query()
            ->with('category')
            ->filter($conditions)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return response()->streamDownload(
            function () use ($contacts): void {
                $stream = fopen('php://output', 'w');

                if ($stream === false) {
                    throw new RuntimeException(
                        'CSV出力ストリームを開けませんでした。'
                    );
                }

                // Excelで文字化けしにくいようにUTF-8 BOMを出力
                fwrite($stream, "\xEF\xBB\xBF");

                // ヘッダー
                fputcsv(
                    $stream,
                    [
                        'ID',
                        '氏名',
                        '性別',
                        'メール',
                        '電話',
                        '住所',
                        '建物',
                        'カテゴリ',
                        '内容',
                        '作成日時',
                    ],
                    ',',
                    '"',
                    '',
                    "\r\n"
                );

                $genderLabels = [
                    1 => '男性',
                    2 => '女性',
                    3 => 'その他',
                ];

                foreach ($contacts as $contact) {
                    fputcsv(
                        $stream,
                        [
                            $contact->id,
                            $contact->first_name.' '.$contact->last_name,
                            $genderLabels[$contact->gender] ?? '',
                            $contact->email,
                            $contact->tel,
                            $contact->address,
                            $contact->building ?? '',
                            $contact->category?->content ?? '',
                            $contact->detail,
                            $contact->created_at?->format('Y-m-d H:i:s') ?? '',
                        ],
                        ',',
                        '"',
                        '',
                        "\r\n"
                    );
                }

                fclose($stream);
            },
            'contacts.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }
}
