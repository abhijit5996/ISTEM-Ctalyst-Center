import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { toast } from "sonner";
import { Upload, Download, AlertTriangle, CheckCircle, Loader2, Info } from "lucide-react";
import { importInstruments } from "@/api/services/instrumentService";

const ImportInstruments = () => {
  const [csvFile, setCsvFile] = useState<File | null>(null);
  const [imageFiles, setImageFiles] = useState<File[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [importResult, setImportResult] = useState<{
    success: boolean;
    imported: number;
    warnings: string[];
  } | null>(null);

  const handleCsvChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      if (file.type === "text/csv" || file.name.endsWith(".csv")) {
        setCsvFile(file);
        toast.success(`CSV file selected: ${file.name}`);
      } else {
        toast.error("Please select a valid CSV file");
        e.target.value = "";
      }
    }
  };

  const handleImagesChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = Array.from(e.target.files || []);
    if (files.length > 0) {
      // Validate that all are images
      const validImages = files.filter((file) => file.type.startsWith("image/"));
      if (validImages.length !== files.length) {
        toast.error("Some files are not valid images. Only images will be added.");
      }
      setImageFiles(validImages);
      toast.success(`${validImages.length} image(s) selected`);
    }
  };

  const handleImport = async () => {
    if (!csvFile) {
      toast.error("Please select a CSV file");
      return;
    }

    setIsLoading(true);
    try {
      const response = await importInstruments(csvFile, imageFiles);
      
      if (response.data.success) {
        setImportResult({
          success: true,
          imported: response.data.imported,
          warnings: response.data.warnings || [],
        });

        toast.success(`${response.data.imported} instruments imported successfully!`);

        // Reset form
        setCsvFile(null);
        setImageFiles([]);
        const csvInput = document.getElementById("csv-input") as HTMLInputElement;
        if (csvInput) csvInput.value = "";
        const imagesInput = document.getElementById("images-input") as HTMLInputElement;
        if (imagesInput) imagesInput.value = "";

      } else {
        toast.error(response.data.message || "Import failed");
        setImportResult({
          success: false,
          imported: 0,
          warnings: [response.data.message || "Unknown error"],
        });
      }
    } catch (error: any) {
      console.error("Import error:", error);
      const errorMessage = error.response?.data?.message || error.message || "Failed to import instruments";
      toast.error(errorMessage);
      setImportResult({
        success: false,
        imported: 0,
        warnings: [errorMessage],
      });
    } finally {
      setIsLoading(false);
    }
  };

  const downloadSampleCsv = () => {
    const sampleData = `instrument_name,category,location,usage_cost,status,description,image
Scanning Electron Microscope (SEM),Scientific,Materials Lab,1200,available,High resolution imaging tool for material analysis,sem.jpg
Atomic Force Microscope (AFM),Scientific,Nanotechnology Lab,800,available,Measures micro and nano scale surface properties,afm.png
X-ray Diffractometer,Scientific,Analysis Lab,950,available,Crystal structure analysis equipment,xrd.jpg
UV-Visible Spectrophotometer,Scientific,Chemistry Lab,600,available,Analyzes light absorption in solution,uvvis.jpg`;

    const blob = new Blob([sampleData], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute("download", "sample_instruments.csv");
    link.style.visibility = "hidden";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    toast.success("Sample CSV downloaded");
  };

  return (
    <div className="p-4 sm:p-6 space-y-6">
      <div>
        <h1 className="text-lg sm:text-xl font-bold mb-2">Bulk Import Instruments</h1>
        <p className="text-sm text-muted-foreground">Upload a CSV file with instrument data and their corresponding images</p>
      </div>

      {/* Alert - Instructions */}
      <Alert className="border-info bg-info/5">
        <Info className="h-4 w-4 text-info" />
        <AlertDescription className="text-sm">
          <strong>Important:</strong> Image filenames in the CSV must exactly match the uploaded image filenames.
          For example, if your CSV has 'sem.jpg', you must upload a file named 'sem.jpg'.
        </AlertDescription>
      </Alert>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Upload Section */}
        <div className="lg:col-span-2 space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Step 1: Select CSV File</CardTitle>
              <CardDescription>Upload your instrument data in CSV format</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="csv-input" className="font-medium">CSV File *</Label>
                <div className="flex items-center gap-3">
                  <Input
                    id="csv-input"
                    type="file"
                    accept=".csv,text/csv"
                    onChange={handleCsvChange}
                    disabled={isLoading}
                    className="cursor-pointer"
                  />
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={downloadSampleCsv}
                    disabled={isLoading}
                    className="whitespace-nowrap"
                  >
                    <Download className="h-4 w-4 mr-2" />
                    Sample CSV
                  </Button>
                </div>
                {csvFile && (
                  <p className="text-xs text-green-600 flex items-center gap-1">
                    <CheckCircle className="h-3 w-3" />
                    Selected: {csvFile.name}
                  </p>
                )}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-base">Step 2: Upload Images</CardTitle>
              <CardDescription>Upload instrument images (optional but recommended)</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="images-input" className="font-medium">Images</Label>
                <Input
                  id="images-input"
                  type="file"
                  accept="image/jpeg,image/png,image/jpg"
                  multiple
                  onChange={handleImagesChange}
                  disabled={isLoading}
                  className="cursor-pointer"
                />
                {imageFiles.length > 0 && (
                  <div className="mt-3 space-y-2">
                    <p className="text-xs font-medium text-muted-foreground">Selected images ({imageFiles.length}):</p>
                    <div className="flex flex-wrap gap-2">
                      {imageFiles.map((img) => (
                        <span key={img.name} className="text-xs bg-secondary text-secondary-foreground px-2 py-1 rounded-md truncate max-w-xs">
                          {img.name}
                        </span>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-base">Step 3: Review & Import</CardTitle>
              <CardDescription>Click the button below to start the import</CardDescription>
            </CardHeader>
            <CardContent>
              <Button
                onClick={handleImport}
                disabled={!csvFile || isLoading}
                size="lg"
                className="w-full"
              >
                {isLoading ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Importing...
                  </>
                ) : (
                  <>
                    <Upload className="mr-2 h-4 w-4" />
                    Import {imageFiles.length > 0 ? "with Images" : "CSV"}
                  </>
                )}
              </Button>
            </CardContent>
          </Card>
        </div>

        {/* Format Preview */}
        <div className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle className="text-base">CSV Format</CardTitle>
              <CardDescription>Required columns (in order)</CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="space-y-2 text-xs">
                <div className="font-mono bg-muted p-2 rounded border border-border text-wrap">
                  <div className="text-muted-foreground truncate">instrument_name,category,</div>
                  <div className="text-muted-foreground truncate">location,usage_cost,status,</div>
                  <div className="text-muted-foreground truncate">description,image</div>
                </div>
              </div>

              <div className="space-y-2">
                <p className="font-medium">Column Details:</p>
                <ul className="space-y-1.5 text-xs">
                  <li><strong>instrument_name</strong><span className="text-destructive">*</span> - Tool name</li>
                  <li><strong>category</strong><span className="text-destructive">*</span> - Type/category</li>
                  <li><strong>location</strong> - Lab location</li>
                  <li><strong>usage_cost</strong> - Cost per hour</li>
                  <li><strong>status</strong> - available/booked</li>
                  <li><strong>description</strong> - Tool details</li>
                  <li><strong>image</strong> - Filename (e.g., sem.jpg)</li>
                </ul>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-base">Tips</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2 text-xs">
              <ul className="space-y-2 list-disc list-inside text-muted-foreground">
                <li>Keep CSV simple: name, category required</li>
                <li>Image names are case-sensitive</li>
                <li>Duplicates (by name) will be skipped</li>
                <li>Import continues even if some rows fail</li>
                <li>Check warnings after import</li>
              </ul>
            </CardContent>
          </Card>
        </div>
      </div>

      {/* Import Results */}
      {importResult && (
        <Card className={importResult.success ? "border-green-200 bg-green-50/50" : "border-red-200 bg-red-50/50"}>
          <CardHeader>
            <div className="flex items-center gap-2">
              {importResult.success ? (
                <CheckCircle className="h-5 w-5 text-green-600" />
              ) : (
                <AlertTriangle className="h-5 w-5 text-red-600" />
              )}
              <CardTitle className="text-base">
                {importResult.success ? "Import Successful!" : "Import Failed"}
              </CardTitle>
            </div>
          </CardHeader>
          <CardContent className="space-y-4">
            {importResult.success && (
              <p className="text-sm font-medium text-green-700">
                ✓ {importResult.imported} instrument(s) imported successfully!
              </p>
            )}

            {importResult.warnings.length > 0 && (
              <div className="space-y-2">
                <p className="text-sm font-medium {importResult.success ? 'text-amber-700' : 'text-red-700'}">
                  {importResult.success ? "⚠ Warnings/Notes:" : "Errors:"}
                </p>
                <div className="space-y-1 max-h-40 overflow-y-auto">
                  {importResult.warnings.map((warning, idx) => (
                    <div key={idx} className="text-xs font-mono text-muted-foreground bg-background p-2 rounded border border-border">
                      {warning}
                    </div>
                  ))}
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      )}

      {/* CSV Format Dialog for Mobile */}
      <Dialog>
        <DialogTrigger asChild>
          <Button variant="outline" className="w-full sm:hidden">
            View Full CSV Format
          </Button>
        </DialogTrigger>
        <DialogContent className="max-w-sm">
          <DialogHeader>
            <DialogTitle>CSV Format Details</DialogTitle>
          </DialogHeader>
          <div className="space-y-4 text-sm">
            <div className="font-mono bg-muted p-3 rounded border border-border text-xs">
              <div>instrument_name,category,</div>
              <div>location,usage_cost,status,</div>
              <div>description,image</div>
            </div>

            <div className="space-y-2">
              <p className="font-medium">Example Row:</p>
              <div className="text-xs bg-muted p-2 rounded border border-border font-mono text-wrap break-words">
                SEM,Scientific,Lab-1,₹1200,available,High-res imaging,sem.jpg
              </div>
            </div>

            <div className="space-y-2">
              <p className="font-medium">Column Details:</p>
              <ul className="space-y-1 text-xs">
                <li><strong>name*</strong> - Instrument name (required)</li>
                <li><strong>category*</strong> - Category (required)</li>
                <li><strong>location</strong> - Lab location</li>
                <li><strong>usage_cost</strong> - Hourly cost</li>
                <li><strong>status</strong> - available/booked</li>
                <li><strong>description</strong> - Details</li>
                <li><strong>image</strong> - Filename match</li>
              </ul>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default ImportInstruments;
