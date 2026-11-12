import { Award, Fish, Flag, Flower2, Repeat, Star, Trophy, Zap } from 'lucide-vue-next';

/**
 * Maps achievement icon keys stored in the database to Lucide components.
 */
const achievementIcons = {
    flag: Flag,
    star: Star,
    repeat: Repeat,
    trophy: Trophy,
    zap: Zap,
    fish: Fish,
    'flower-2': Flower2,
};

export function achievementIcon(key) {
    return achievementIcons[key] ?? Award;
}
