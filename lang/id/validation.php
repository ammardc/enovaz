<?php

return [
    'accepted' => ':attribute harus disetujui.',
    'after_or_equal' => ':attribute harus sama atau setelah :date.',
    'between' => ['numeric' => ':attribute harus antara :min dan :max.'],
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'date' => ':attribute harus berupa tanggal yang valid.',
    'digits' => ':attribute harus berisi :digits angka.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'exists' => ':attribute yang dipilih tidak tersedia.',
    'file' => ':attribute harus berupa berkas.',
    'image' => ':attribute harus berupa gambar.',
    'in' => ':attribute yang dipilih tidak valid.',
    'integer' => ':attribute harus berupa angka bulat.',
    'max' => ['numeric' => ':attribute tidak boleh lebih dari :max.', 'file' => ':attribute tidak boleh lebih dari :max KB.', 'string' => ':attribute tidak boleh lebih dari :max karakter.'],
    'mimes' => ':attribute harus berformat :values.',
    'min' => ['numeric' => ':attribute minimal :min.', 'string' => ':attribute minimal :min karakter.'],
    'not_in' => ':attribute tidak boleh bernilai tersebut.',
    'required' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'unique' => ':attribute sudah digunakan.',
    'uuid' => ':attribute tidak valid.',
    'attributes' => [
        'name'=>'nama','email'=>'email','password'=>'kata sandi','password_confirmation'=>'konfirmasi kata sandi',
        'amount'=>'nominal','amount_idr'=>'nominal','quantity'=>'jumlah','reason'=>'alasan','code'=>'kode',
        'image'=>'gambar','file'=>'file','category_id'=>'kategori','price_idr'=>'harga',
    ],
];
