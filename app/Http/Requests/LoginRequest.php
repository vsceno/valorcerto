<?php

namespace App\Http\Requests;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /** Tentativas permitidas antes do bloqueio temporário. */
    private const MAX_TENTATIVAS = 5;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => 'e-mail',
            'password' => 'senha',
        ];
    }

    /**
     * Autentica respeitando o limite de tentativas.
     *
     * @throws ValidationException
     */
    public function autenticar(): void
    {
        $this->garantirQueNaoEstaBloqueado();

        $credenciais = $this->only('email', 'password') + ['ativo' => true];

        if (! Auth::attempt($credenciais, $this->boolean('lembrar'))) {
            RateLimiter::hit($this->chaveDeTentativas());

            throw ValidationException::withMessages([
                'email' => 'As credenciais informadas não conferem, ou o usuário está inativo.',
            ]);
        }

        RateLimiter::clear($this->chaveDeTentativas());
    }

    /**
     * @throws ValidationException
     */
    private function garantirQueNaoEstaBloqueado(): void
    {
        if (! RateLimiter::tooManyAttempts($this->chaveDeTentativas(), self::MAX_TENTATIVAS)) {
            return;
        }

        Event::dispatch(new Lockout($this));

        $segundos = RateLimiter::availableIn($this->chaveDeTentativas());

        throw ValidationException::withMessages([
            'email' => sprintf(
                'Muitas tentativas de acesso. Tente novamente em %d %s.',
                $segundos > 60 ? ceil($segundos / 60) : $segundos,
                $segundos > 60 ? 'minutos' : 'segundos'
            ),
        ]);
    }

    private function chaveDeTentativas(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
