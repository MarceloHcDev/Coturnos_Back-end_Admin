<?php

namespace App\Models;

enum StockMovementType: string
{
    case INBOUND = 'in';            // entrada (compra, reposição, devolução)
    case OUTBOUND = 'out';          // saída manual (perda, avaria, brinde)
    case ADJUSTMENT = 'adjustment'; // ajuste de inventário: define a quantidade absoluta
}
