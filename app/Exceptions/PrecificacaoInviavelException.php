<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Disparada quando a carga tributária somada à margem desejada consome 100% ou
 * mais do preço, tornando o divisor da fórmula nulo ou negativo.
 */
class PrecificacaoInviavelException extends RuntimeException {}
