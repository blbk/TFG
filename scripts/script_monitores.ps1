<# ============================================================================
   Proyecto      : Sistema de Gestión CMDB para TFG
   Archivo       : script_monitores.ps1
   Autor         : Javier Moyano Vizcaíno
   Curso         : 2025/2026
  
   Descripción	 : Script para identificar los monitores conectados al PC
				   Usa la infraestructura WMI y lee los datos UEFI EDID del monitor
   Observaciones : Si es un portátil se obvia el monitor principal (integrado)
   ============================================================================
#>

# Se importa la librería .NET de Windows para leer información de la pantalla.
Add-Type -AssemblyName System.Windows.Forms

function Decode-EdidString {
	# Para decodificar los datos EDID del monitor
    param($Bytes)

    if (-not $Bytes) { return $null }
    ([System.Text.Encoding]::ASCII.GetString($Bytes)).Trim([char]0)
}

function Get-TipoMonitor {
	# Si es un portátil y es monitor principal, se considera que está integrado
    param( $EsPortatil, $MonitorPrincipal )

    if ($EsPortatil -and $MonitorPrincipal) {
            return "Integrado"
        }   return "Externo"
}

$equipo = $env:COMPUTERNAME
$fechaInventario = Get-Date -Format "yyyy-MM-dd HH:mm:ss"

# Datos WMI
$monitores   = Get-CimInstance -Namespace root\wmi -ClassName WmiMonitorID
$physical   = Get-CimInstance -Namespace root\wmi -ClassName WmiMonitorBasicDisplayParams
$conexion = Get-CimInstance -Namespace root\wmi -ClassName WmiMonitorConnectionParams

# Tipo de equipo (Para saber si es un portátil o similar)
$chasis = Get-CimInstance Win32_SystemEnclosure
$tipoEquipo = $chasis.ChassisTypes[0]
$EsPortatil = $tipoEquipo -in 8,9,10,14

# Pantallas según Windows
$pantallas = [System.Windows.Forms.Screen]::AllScreens

$result = for ($i = 0; $i -lt $monitores.Count; $i++) {

    $m = $monitores[$i]

    $p = if ($i -lt $physical.Count) { $physical[$i] } else { $null }
    $c = if ($i -lt $conexion.Count) { $conexion[$i] } else { $null }
    $s = if ($i -lt $pantallas.Count)  { $pantallas[$i] } else { $null }

    # Tipo monitor (filtrar)
    $tipoMonitor = "Desconocido"
    if ($c) {
		# FILTRO: no se consideran los monitores integrados
		$tipoMonitor = Get-TipoMonitor $EsPortatil $s.Primary
		if ($tipoMonitor -eq "Integrado") {
			continue
		}
	}

    # Datos EDID
    $fabricante = Decode-EdidString $m.ManufacturerName
    $modelo     = Decode-EdidString $m.UserFriendlyName
    $serie      = Decode-EdidString $m.SerialNumberID

    # Resolución
    $resolucion = $null
    $principal  = $false

    if ($s) {
        $resolucion = "$($s.Bounds.Width)x$($s.Bounds.Height)"
        $principal  = $s.Primary
    }

    # Tamaño físico
    $anchoCm = $null
    $altoCm  = $null
    $diagonal = $null

    if ($p) {
        $anchoCm = $p.MaxHorizontalImageSize
        $altoCm  = $p.MaxVerticalImageSize

        if ($anchoCm -gt 0 -and $altoCm -gt 0) {
            $diagonal = [math]::Round(
                [math]::Sqrt(($anchoCm * $anchoCm) + ($altoCm * $altoCm)) / 2.54, 0 )
        }
    }

    [PSCustomObject]@{
        Equipo            = $equipo
        FechaInventario   = $fechaInventario

       # Instancia      = $m.InstanceName #Considerar solo si se va a usar en búsquedas
        Activo            = $m.Active

        Fabricante        = $fabricante
        Modelo            = $modelo
        NumeroSerie       = $serie

        Resolucion        = $resolucion
        MonitorPrincipal  = $principal

        AnchoCm           = $anchoCm
        AltoCm            = $altoCm
        DiagonalPulg	  = $diagonal

        TipoMonitor       = $tipoMonitor

       # OrigenInventario  = "WMI + EDID + API pantallas"
    }
}

# Mostrar resultados
$result | Format-List *

# Exportación CSV
$result | Export-Csv ".\Monitores-$equipo.csv" -NoTypeInformation -Encoding UTF8