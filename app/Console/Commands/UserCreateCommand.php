<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class UserCreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create
                            {email? : Адрес электронной почты сотрудника или его логин}
                            {password? : Пароль для входа}
                            {name? : Имя пользователя}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создает нового пользователя';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!$this->argument('email'))
            $errors[] = "Укажите email пользователя, передав его первым аргументом";

        if (!$this->argument('password'))
            $errors[] = "Укажите пароль для входа, передав его вторым аргументом";

        if (!$this->argument('name'))
            $errors[] = "Укажите имя пользователя, передав его третим аргументом аргументом";

        if (count($errors ?? [])) {

            $this->error(" Ошибка создания нового пользователя: ");

            foreach ($errors as $key => $message) {
                $number = $key + 1;
                $this->line("<fg=red>{$number}. {$message}</>");
            }

            return 0;
        }

        $row = new User;
        $row->email = $this->argument('email');
        $row->password = Hash::make($this->argument('password'));
        $row->name = $this->argument('name');

        $row->save();

        $this->info("Создан новый пользователь с id:{$row->id}");

        return 0;
    }
}
