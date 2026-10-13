<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Indonesian messages for the rules used by the admin panel. Rules missing
    | here fall back to the English messages bundled with the framework.
    |
    */

    'accepted' => ':Attribute harus diterima.',
    'alpha_dash' => ':Attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'array' => ':Attribute harus berupa daftar.',
    'boolean' => ':Attribute harus bernilai benar atau salah.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi saat ini salah.',
    'date' => ':Attribute bukan tanggal yang valid.',
    'distinct' => ':Attribute memiliki nilai yang sama dengan isian lain.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'enum' => ':Attribute yang dipilih tidak valid.',
    'exists' => ':Attribute yang dipilih tidak valid.',
    'file' => ':Attribute harus berupa berkas.',
    'image' => ':Attribute harus berupa gambar.',
    'in' => ':Attribute yang dipilih tidak valid.',
    'integer' => ':Attribute harus berupa bilangan bulat.',
    'max' => [
        'array' => ':Attribute maksimal berisi :max item.',
        'file' => 'Ukuran :attribute maksimal :max kilobita.',
        'numeric' => ':Attribute maksimal bernilai :max.',
        'string' => ':Attribute maksimal :max karakter.',
    ],
    'mimes' => ':Attribute harus berupa berkas berjenis: :values.',
    'min' => [
        'array' => ':Attribute minimal berisi :min item.',
        'file' => 'Ukuran :attribute minimal :min kilobita.',
        'numeric' => ':Attribute minimal bernilai :min.',
        'string' => ':Attribute minimal :min karakter.',
    ],
    'password' => [
        'letters' => ':Attribute harus berisi minimal satu huruf.',
        'mixed' => ':Attribute harus berisi minimal satu huruf besar dan satu huruf kecil.',
        'numbers' => ':Attribute harus berisi minimal satu angka.',
        'symbols' => ':Attribute harus berisi minimal satu simbol.',
        'uncompromised' => ':Attribute ini pernah bocor di internet. Silakan pilih :attribute lain.',
    ],
    'present' => ':Attribute wajib ada.',
    'prohibited' => ':Attribute tidak boleh diisi.',
    'required' => ':Attribute wajib diisi.',
    'required_with' => ':Attribute wajib diisi bila :values diisi.',
    'size' => [
        'array' => ':Attribute harus berisi :size item.',
        'file' => 'Ukuran :attribute harus :size kilobita.',
        'numeric' => ':Attribute harus bernilai :size.',
        'string' => ':Attribute harus :size karakter.',
    ],
    'string' => ':Attribute harus berupa teks.',
    'unique' => ':Attribute sudah dipakai.',
    'uploaded' => ':Attribute gagal diunggah.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'title' => 'judul',
        'slug' => 'alamat (slug)',
        'category' => 'kategori',
        'excerpt' => 'paragraf pembuka',
        'body' => 'isi',
        'author' => 'penulis',
        'is_published' => 'status terbit',
        'published_at' => 'tanggal terbit',
        'image' => 'foto',
        'images' => 'foto',
        'images.*' => 'foto',
        'image_ids' => 'foto',
        'image_ids.*' => 'foto',
        'description' => 'deskripsi',
        'icon' => 'ikon',
        'audience' => 'peserta',
        'schedule' => 'jadwal',
        'features' => 'daftar isi ruangan',
        'features.*' => 'isi ruangan',
        'majors' => 'jurusan',
        'majors.*' => 'jurusan',
        'ids' => 'urutan',
        'ids.*' => 'urutan',
        'name' => 'nama',
        'email' => 'email',
        'password' => 'kata sandi',
        'current_password' => 'kata sandi saat ini',
        'search' => 'pencarian',
        'status' => 'status',
        'offset' => 'offset',
        'limit' => 'limit',
    ],

];
