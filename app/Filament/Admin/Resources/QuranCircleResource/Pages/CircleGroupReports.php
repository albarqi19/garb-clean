<?php

namespace App\Filament\Admin\Resources\QuranCircleResource\Pages;

use App\Filament\Admin\Resources\QuranCircleResource;
use App\Models\CircleGroup;
use App\Models\QuranCircle;
use App\Models\Student;
use Filament\Infolists\Components\Card;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Filament\Resources\Pages\Page as ResourcePage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CircleGroupReports extends ResourcePage
{
    protected static string $resource = QuranCircleResource::class;
    
    protected static string $view = 'filament.admin.resources.quran-circle-resource.pages.circle-group-reports';
    
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static ?string $navigationLabel = 'تقارير الحلقات الفرعية';
    
    protected static ?string $title = 'تقارير وإحصاءات الحلقات الفرعية';
    
    public $quranCircleId = null;
    public $selectedCircleGroup = null;
    
    public function mount(): void
    {
        $this->authorizeAccess();
    }
    
    public function getQuranCircles()
    {
        return QuranCircle::where('circle_type', 'حلقة جماعية')->get()
            ->mapWithKeys(function ($circle) {
                return [$circle->id => $circle->name];
            });
    }
    
    public function getCircleGroups()
    {
        if ($this->quranCircleId) {
            return CircleGroup::where('quran_circle_id', $this->quranCircleId)
                ->get()
                ->mapWithKeys(function ($group) {
                    return [$group->id => $group->name];
                });
        }
        
        return [];
    }
    
    public function getStudentsCount()
    {
        if ($this->selectedCircleGroup) {
            return Student::where('circle_group_id', $this->selectedCircleGroup)->count();
        }
        
        if ($this->quranCircleId) {
            return Student::whereHas('circleGroup', function (Builder $query) {
                $query->where('quran_circle_id', $this->quranCircleId);
            })->count();
        }
        
        return 0;
    }
    
    public function getActiveCircleGroupsCount()
    {
        if ($this->quranCircleId) {
            return CircleGroup::where('quran_circle_id', $this->quranCircleId)
                ->where('status', 'نشطة')
                ->count();
        }
        
        return 0;
    }
    
    public function getStudentsByGender()
    {
        if ($this->selectedCircleGroup) {
            $query = Student::where('circle_group_id', $this->selectedCircleGroup);
        } elseif ($this->quranCircleId) {
            $query = Student::whereHas('circleGroup', function (Builder $query) {
                $query->where('quran_circle_id', $this->quranCircleId);
            });
        } else {
            return [
                'ذكر' => 0,
                'أنثى' => 0
            ];
        }
        
        return [
            'ذكر' => (clone $query)->where('gender', 'ذكر')->count(),
            'أنثى' => (clone $query)->where('gender', 'أنثى')->count(),
        ];
    }
    
    public function getStudentsByLevel()
    {
        if ($this->selectedCircleGroup) {
            $query = Student::where('circle_group_id', $this->selectedCircleGroup);
        } elseif ($this->quranCircleId) {
            $query = Student::whereHas('circleGroup', function (Builder $query) {
                $query->where('quran_circle_id', $this->quranCircleId);
            });
        } else {
            return [];
        }
        
        return $query->select('current_level', DB::raw('count(*) as total'))
            ->groupBy('current_level')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->current_level => $item->total];
            })
            ->toArray();
    }
    
    public function getCircleGroupsByStatus()
    {
        if ($this->quranCircleId) {
            return CircleGroup::where('quran_circle_id', $this->quranCircleId)
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->status => $item->total];
                })
                ->toArray();
        }
        
        return [];
    }
    
    public function getSelectedCircleGroup()
    {
        if ($this->selectedCircleGroup) {
            return CircleGroup::find($this->selectedCircleGroup);
        }
        
        return null;
    }
    
    public function getSelectedQuranCircle()
    {
        if ($this->quranCircleId) {
            return QuranCircle::find($this->quranCircleId);
        }
        
        return null;
    }
}
