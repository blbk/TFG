<# ========================================================================
   Proyecto      : Sistema de Gestión CMDB para TFG
   Archivo       : script_impresoras.ps1
   Autor         : Javier Moyano Vizcaíno
   Curso         : 2025/2026
  
   Descripción	 : Script para obtener información técnica de las impresoras.
				   A través de la API de Windows se obtienen los datos que 
				   tienen los computadores en su sección de impresoras instaladas.
   Observaciones : El código se ha obtenido mediante IA (ChatGPT) 
				   y se ha revisado posteriormente.
   ========================================================================
#>

$printers = Get-CimInstance Win32_Printer

$result = foreach($p in $printers)
{
    $port = Get-CimInstance Win32_TCPIPPrinterPort |
            Where-Object {$_.Name -eq $p.PortName}

    [PSCustomObject]@{
        Nombre      = $p.Name
        Driver      = $p.DriverName
        Puerto      = $p.PortName
        IP          = $port.HostAddress
        Red         = $p.Network
        Compartida  = $p.Shared
        Predeterminada = $p.Default
        Ubicacion   = $p.Location
        Estado      = $p.PrinterStatus
    }
}

$result | Format-Table -AutoSize

# Exportación CSV
$result | Export-Csv ".\Impresoras-$equipo.csv" -NoTypeInformation -Encoding UTF8